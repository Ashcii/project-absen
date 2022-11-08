<?php

declare (strict_types=1);
namespace Rector\BetterPhpDocParser\PhpDocManipulator;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
final class VarAnnotationManipulator
{
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger
     */
    private $phpDocTypeChanger;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    public function __construct(\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory $phpDocInfoFactory, \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger $phpDocTypeChanger, \Rector\Core\PhpParser\Node\BetterNodeFinder $betterNodeFinder)
    {
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->phpDocTypeChanger = $phpDocTypeChanger;
        $this->betterNodeFinder = $betterNodeFinder;
    }
    public function decorateNodeWithInlineVarType(\PhpParser\Node $node, \PHPStan\Type\TypeWithClassName $typeWithClassName, string $variableName) : void
    {
        $phpDocInfo = $this->resolvePhpDocInfo($node);
        // already done
        if ($phpDocInfo->getVarTagValueNode() !== null) {
            return;
        }
        $fullyQualifiedIdentifierTypeNode = new \Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode($typeWithClassName->getClassName());
        $varTagValueNode = new \PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode($fullyQualifiedIdentifierTypeNode, '$' . $variableName, '');
        $phpDocInfo->addTagValueNode($varTagValueNode);
        $phpDocInfo->makeSingleLined();
    }
    public function decorateNodeWithType(\PhpParser\Node $node, \PHPStan\Type\Type $staticType) : void
    {
        if ($staticType instanceof \PHPStan\Type\MixedType) {
            return;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $this->phpDocTypeChanger->changeVarType($phpDocInfo, $staticType);
    }
    private function resolvePhpDocInfo(\PhpParser\Node $node) : \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
    {
        $currentStmt = $this->betterNodeFinder->resolveCurrentStatement($node);
        if ($currentStmt instanceof \PhpParser\Node\Stmt\Expression) {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($currentStmt);
        } else {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        }
        $phpDocInfo->makeSingleLined();
        return $phpDocInfo;
    }
}
