<?php

declare(strict_types=1);

namespace MartinGeorgiev\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Implementation of PostgreSql STRING_AGG().
 *
 * @see https://www.postgresql.org/docs/9.5/functions-aggregate.html
 * @since 1.4
 *
 * @author Martin Georgiev <martin.georgiev@gmail.com>
 */
class StringAgg extends BaseFunction
{
    private bool $isDistinct = false;

    private Node $expression;

    private Node $delimiter;

    private OrderByClause $orderByClause;

    protected function customiseFunction(): void
    {
        $this->setFunctionPrototype('string_agg(%s%s, %s%s)');
    }

    public function parse(Parser $parser): void
    {
        $this->customiseFunction();

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(TokenType::T_DISTINCT)) {
            $parser->match(TokenType::T_DISTINCT);
            $this->isDistinct = true;
        }

        $this->expression = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->delimiter = $parser->StringPrimary();

        if ($lexer->isNextToken(TokenType::T_ORDER)) {
            $this->orderByClause = $parser->OrderByClause();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $dispatched = [
            $this->isDistinct ? 'distinct ' : '',
            $this->expression->dispatch($sqlWalker),
            $this->delimiter->dispatch($sqlWalker),
            isset($this->orderByClause) ? $this->orderByClause->dispatch($sqlWalker) : '',
        ];

        return \vsprintf($this->functionPrototype, $dispatched);
    }
}
