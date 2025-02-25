<?php

declare(strict_types=1);

namespace MartinGeorgiev\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * @since 0.1
 *
 * @author Martin Georgiev <martin.georgiev@gmail.com>
 */
abstract class BaseFunction extends FunctionNode
{
    protected string $functionPrototype;

    /**
     * @var list<string>
     */
    protected array $nodesMapping = [];

    /**
     * @var list<Node|null>
     */
    protected array $nodes = [];

    abstract protected function customiseFunction(): void;

    protected function setFunctionPrototype(string $functionPrototype): void
    {
        $this->functionPrototype = $functionPrototype;
    }

    protected function addNodeMapping(string $parserMethod): void
    {
        $this->nodesMapping[] = $parserMethod;
    }

    public function parse(Parser $parser): void
    {
        $this->customiseFunction();

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->feedParserWithNodes($parser);
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * Feeds given parser with previously set nodes.
     */
    protected function feedParserWithNodes(Parser $parser): void
    {
        $nodesMappingCount = \count($this->nodesMapping);
        $lastNode = $nodesMappingCount - 1;
        for ($i = 0; $i < $nodesMappingCount; $i++) {
            $parserMethod = $this->nodesMapping[$i];
            $this->nodes[$i] = $parser->{$parserMethod}();
            if ($i < $lastNode) {
                $parser->match(TokenType::T_COMMA);
            }
        }
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $dispatched = [];
        foreach ($this->nodes as $node) {
            $dispatched[] = $node instanceof Node ? $node->dispatch($sqlWalker) : 'null';
        }

        return \vsprintf($this->functionPrototype, $dispatched);
    }
}
