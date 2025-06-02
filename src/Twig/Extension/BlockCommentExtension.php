<?php

namespace Shopware\Development\Twig\Extension;

use Shopware\Development\Twig\Extension\NodeVisitor\BlockCommentNodeVisitor;
use Twig\Extension\AbstractExtension;

class BlockCommentExtension extends AbstractExtension
{
    public function __construct(private readonly string $kernelRootDir, private readonly array $twigExcludeKeywords)
    {
    }

    /**
     * @return BlockCommentNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new BlockCommentNodeVisitor($this->kernelRootDir, $this->twigExcludeKeywords)];
    }
}
