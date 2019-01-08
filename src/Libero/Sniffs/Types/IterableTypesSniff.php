<?php

declare(strict_types=1);

namespace Libero\CodingStandard\Libero\Sniffs\Types;

use LogicException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\ParameterTypeHint;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use SlevomatCodingStandard\Helpers\ReturnTypeHint;
use Traversable;
use function array_map;
use function array_reduce;
use function count;
use function get_class;
use function implode;
use function in_array;
use function is_a;
use function is_string;
use function sprintf;
use function strpos;
use const T_FUNCTION;
use const T_VARIABLE;

final class IterableTypesSniff implements Sniff
{
    public const CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT = 'MissingPropertyIterableType';
    public const CODE_MISSING_FUNCTION_ITERABLE_ARGUMENT_TYPE_HINT = 'MissingIterableArgumentType';
    public const CODE_MISSING_FUNCTION_ITERABLE_RETURN_TYPE_HINT = 'MissingIterableReturnType';
    public const CODE_PROPERTY_ITERABLE_TYPE_HINT_GENERICS = 'PropertyIterableTypeGenerics';
    public const CODE_ARGUMENT_ITERABLE_TYPE_HINT_GENERICS = 'ArgumentIterableTypeGenerics';
    public const CODE_METHOD_ITERABLE_RETURN_TYPE_HINT_GENERICS = 'MethodIterableReturnTypeGenerics';
    public const SNIFF_NAME = 'Libero.Types.IterableTypes';

    /** @var Lexer */
    private $lexer;

    /** @var PhpDocParser */
    private $parser;

    public function __construct()
    {
        $this->lexer = new Lexer();
        $this->parser = new PhpDocParser(new TypeParser(), new ConstExprParser());
    }

    /**
     * @return array<int>
     */
    public function register() : array
    {
        return [
            T_VARIABLE,
            T_FUNCTION,
        ];
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
    public function process(File $file, $pointer) : ?int
    {
        $token = $file->getTokens()[$pointer];

        if ($token['code'] === T_VARIABLE && PropertyHelper::isProperty($file, $pointer)) {
            $this->checkProperty($file, $pointer);

            return null;
        }

        if ($token['code'] === T_FUNCTION) {
            $this->checkFunctionArgumentTypes($file, $pointer);
            $this->checkFunctionReturnType($file, $pointer);

            return null;
        }

        return null;
    }

    private function checkProperty(File $file, int $propertyPointer) : void
    {
        $phpDoc = $this->getPhpDoc($file, $propertyPointer);
        $typeAnnotation = $phpDoc instanceof PhpDocNode ? $phpDoc->getVarTagValues()[0] ?? null : null;

        if (!$typeAnnotation instanceof VarTagValueNode) {
            return;
        }

        $correctType = $this->formatType($this->toRealType($typeAnnotation->type));

        if ($correctType !== $this->formatType($typeAnnotation->type)) {
            $fix = $file->addFixableError(
                sprintf(
                    'Fix %s.',
                    PropertyHelper::getFullyQualifiedName($file, $propertyPointer)
                ),
                $propertyPointer,
                self::CODE_PROPERTY_ITERABLE_TYPE_HINT_GENERICS
            );

            if (!$fix) {
                return;
            }

            $file->fixer->beginChangeset();

            $typeAnnotationSlevomat = AnnotationHelper::getAnnotationsByName(
                $file,
                $propertyPointer,
                '@var'
            )[0];

            for ($i = $typeAnnotationSlevomat->getStartPointer() + 2; $i < $typeAnnotationSlevomat->getEndPointer(
            ); $i++) {
                $file->fixer->replaceToken($i, '');
            }
            $file->fixer->replaceToken($typeAnnotationSlevomat->getEndPointer(), $correctType);

            $file->fixer->endChangeset();

            return;
        }

        if ($typeAnnotation->type instanceof IdentifierTypeNode && $this->isIterable($typeAnnotation->type)) {
            $file->addError(
                sprintf(
                    'Fix %s.',
                    PropertyHelper::getFullyQualifiedName($file, $propertyPointer)
                ),
                $propertyPointer,
                self::CODE_MISSING_PROPERTY_ITERABLE_TYPE_HINT
            );

            return;
        }
    }

    private function checkFunctionArgumentTypes(File $file, int $functionPointer) : void
    {
        $phpDoc = $this->getPhpDoc($file, $functionPointer);
        $arguments = array_map([$this, 'toType'], FunctionHelper::getParametersTypeHints($file, $functionPointer));

        $argumentAnnotations = array_reduce(
            $phpDoc instanceof PhpDocNode ? $phpDoc->getParamTagValues() : [],
            function (array $carry, ParamTagValueNode $node) : array {
                $carry[$node->parameterName] = $node;

                return $carry;
            },
            []
        );

        foreach ($arguments as $argument => $type) {
            if ($type instanceof TypeNode && !$this->isIterable($type)) {
                continue;
            }

            if (!$type instanceof TypeNode && !isset($argumentAnnotations[$argument])) {
                continue;
            }

            if (!isset($argumentAnnotations[$argument])) {
                $file->addError(
                    sprintf(
                        'Argument %s on %s does not have @param annotation.',
                        $argument,
                        FunctionHelper::getFullyQualifiedName($file, $functionPointer)
                    ),
                    $functionPointer,
                    self::CODE_MISSING_FUNCTION_ITERABLE_ARGUMENT_TYPE_HINT
                );

                continue;
            }

            $correctType = $this->formatType($this->toRealType($argumentAnnotations[$argument]->type, $type));

            if ($correctType !== $this->formatType($argumentAnnotations[$argument]->type)) {
                $fix = $file->addFixableError(
                    sprintf(
                        'Fix %s.',
                        FunctionHelper::getFullyQualifiedName($file, $functionPointer)
                    ),
                    $functionPointer,
                    self::CODE_ARGUMENT_ITERABLE_TYPE_HINT_GENERICS
                );

                if (!$fix) {
                    continue;
                }

                $file->fixer->beginChangeset();

                $argumentAnnotationsSlevomat = AnnotationHelper::getAnnotationsByName(
                    $file,
                    $functionPointer,
                    '@param'
                );

                foreach ($argumentAnnotationsSlevomat as $argumentAnnotationSlevomat) {
                    if (false === strpos($argumentAnnotationSlevomat->getContent() ?? '', $argument)) {
                        continue;
                    }

                    $startPointer = $argumentAnnotationSlevomat->getStartPointer() + 2;
                    $endPointer = $argumentAnnotationSlevomat->getEndPointer();

                    for ($i = $startPointer; $i < $endPointer; $i++) {
                        $file->fixer->replaceToken($i, '');
                    }

                    $file->fixer->replaceToken(
                        $argumentAnnotationSlevomat->getEndPointer(),
                        "{$correctType} {$argument}"
                    );

                    break;
                }

                $file->fixer->endChangeset();
            }
        }
    }

    private function checkFunctionReturnType(File $file, int $functionPointer) : void
    {
        $phpDoc = $this->getPhpDoc($file, $functionPointer);
        $returnType = $this->toType(FunctionHelper::findReturnTypeHint($file, $functionPointer));
        $returnAnnotation = $phpDoc instanceof PhpDocNode ? $phpDoc->getReturnTagValues()[0] ?? null : null;

        if ($returnType instanceof TypeNode && !$this->isIterable($returnType)) {
            return;
        }

        if ($returnType instanceof TypeNode
            &&
            $this->isIterable($returnType)
            &&
            !$returnAnnotation instanceof ReturnTagValueNode
        ) {
            $file->addError(
                sprintf(
                    'Function %s does not have @var annotation.',
                    FunctionHelper::getFullyQualifiedName($file, $functionPointer)
                ),
                $functionPointer,
                self::CODE_MISSING_FUNCTION_ITERABLE_RETURN_TYPE_HINT
            );

            return;
        }

        if (!$returnAnnotation instanceof ReturnTagValueNode) {
            return;
        }

        $correctType = $this->formatType($this->toRealType($returnAnnotation->type, $returnType));

        if ($correctType !== $this->formatType($returnAnnotation->type)) {
            $fix = $file->addFixableError(
                sprintf(
                    'Fix %s.',
                    FunctionHelper::getFullyQualifiedName($file, $functionPointer)
                ),
                $functionPointer,
                self::CODE_METHOD_ITERABLE_RETURN_TYPE_HINT_GENERICS
            );

            if (!$fix) {
                return;
            }

            $file->fixer->beginChangeset();

            $returnAnnotationSlevomat = AnnotationHelper::getAnnotationsByName(
                $file,
                $functionPointer,
                '@return'
            )[0];

            $startPointer = $returnAnnotationSlevomat->getStartPointer() + 2;
            $endPointer = $returnAnnotationSlevomat->getEndPointer();

            for ($i = $startPointer; $i < $endPointer; $i++) {
                $file->fixer->replaceToken($i, '');
            }

            $file->fixer->replaceToken($returnAnnotationSlevomat->getEndPointer(), $correctType);

            $file->fixer->endChangeset();
        }

        return;
    }

    private function getPhpDoc(File $file, int $pointer) : ?PhpDocNode
    {
        $phpDoc = DocCommentHelper::getDocComment($file, $pointer);

        return is_string($phpDoc) ? $this->getPhpDocFromString($phpDoc) : null;
    }

    private function getPhpDocFromString(string $phpDoc) : PhpDocNode
    {
        $tokens = $this->lexer->tokenize("/**\n{$phpDoc}\n*/");

        return $this->parser->parse(new TokenIterator($tokens));
    }

    /**
     * @param ParameterTypeHint|ReturnTypeHint|null $typeHint
     */
    private function toType($typeHint) : ?TypeNode
    {
        switch (true) {
            case $typeHint instanceof ParameterTypeHint:
                $type = $typeHint->getTypeHint();
                $isNullable = $typeHint->isNullable();
                break;
            case $typeHint instanceof ReturnTypeHint:
                $type = $typeHint->getTypeHint();
                $isNullable = $typeHint->isNullable();
                break;
            default:
                return null;
        }

        return $this->getPhpDocFromString(sprintf('@var %s%s', $isNullable ? '?' : '', $type))
            ->getVarTagValues()[0]->type;
    }

    private function isIterable(TypeNode $type) : bool
    {
        if ($type instanceof ArrayTypeNode || $type instanceof GenericTypeNode) {
            return true;
        }

        if ($type instanceof IdentifierTypeNode && in_array($type->name, ['array', 'iterable'])) {
            return true;
        }

        if ($type instanceof IdentifierTypeNode && is_a($type->name, Traversable::class, true)) {
            return true;
        }

        return false;
    }

    private function toRealType(TypeNode $type, ?TypeNode $realType = null) : TypeNode
    {
        switch (true) {
            case $type instanceof ArrayTypeNode:
                if (!$realType instanceof TypeNode) {
                    $realType = new IdentifierTypeNode('array');
                }

                if (!$realType instanceof IdentifierTypeNode || !$this->isIterable($realType)) {
                    throw new LogicException('Expected array or iterable');
                }

                return new GenericTypeNode($realType, [$type->type]);
            case $type instanceof GenericTypeNode:
                return new GenericTypeNode($type->type, array_map([$this, 'toRealType'], $type->genericTypes));
            case $type instanceof IntersectionTypeNode:
                return new IntersectionTypeNode(array_map([$this, 'toRealType'], $type->types));
            case $type instanceof NullableTypeNode:
                return new UnionTypeNode([$type->type, new IdentifierTypeNode('null')]);
            case $type instanceof UnionTypeNode:
                if (2 === count($type->types)) {
                    if ($type->types[0] instanceof IdentifierTypeNode
                        &&
                        is_a($type->types[0]->name, Traversable::class, true)
                        &&
                        $type->types[1] instanceof ArrayTypeNode
                    ) {
                        return new GenericTypeNode($type->types[0], [$type->types[1]->type]);
                    } elseif ($type->types[1] instanceof IdentifierTypeNode
                        &&
                        is_a($type->types[1]->name, Traversable::class, true)
                        &&
                        $type->types[0] instanceof ArrayTypeNode
                    ) {
                        return new GenericTypeNode($type->types[1], [$type->types[0]->type]);
                    }
                }

                return new UnionTypeNode(array_map([$this, 'toRealType'], $type->types));
            default:
                return $type;
        }
    }

    private function formatType(TypeNode $type) : string
    {
        switch (true) {
            case $type instanceof ArrayTypeNode:
                return $this->formatType($type->type).'[]';
            case $type instanceof GenericTypeNode:
                return sprintf(
                    '%s<%s>',
                    $this->formatType($type->type),
                    implode(', ', array_map([$this, 'formatType'], $type->genericTypes))
                );
            case $type instanceof IdentifierTypeNode:
                return $type->name;
            case $type instanceof IntersectionTypeNode:
                return implode('&', array_map([$this, 'formatType'], $type->types));
            case $type instanceof UnionTypeNode:
                return implode('|', array_map([$this, 'formatType'], $type->types));
            default:
                throw new LogicException('Unknown type '.get_class($type));
        }
    }
}
