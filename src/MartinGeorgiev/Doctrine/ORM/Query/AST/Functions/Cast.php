<?php

declare(strict_types=1);

namespace MartinGeorgiev\Doctrine\ORM\Query\AST\Functions;

use Doctrine\Common\Lexer\Token;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Implementation of PostgreSql CAST().
 *
 * @see https://www.postgresql.org/docs/current/sql-createcast.html
 * @see https://github.com/beberlei/DoctrineExtensions/blob/f3536d881637f6ddc7ca1d6595d18c15e06eb1d9/src/Query/Mysql/Cast.php
 * @since 2.0
 *
 * @author Mathieu Piot <https://github.com/mpiot>
 */
class Cast extends FunctionNode
{
    public Node $sourceType;

    public string $targetType;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->sourceType = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_AS);
        $parser->match(TokenType::T_IDENTIFIER);

        $lexer = $parser->getLexer();
        $token = $lexer->token;
        if (!$token instanceof Token) {
            return;
        }
        if (!\is_string($token->value)) {
            return;
        }

        $type = $token->value;
        if ($lexer->isNextToken(TokenType::T_OPEN_PARENTHESIS)) {
            $parser->match(TokenType::T_OPEN_PARENTHESIS);
            $parameter = $parser->Literal();
            $parameters = [$parameter->value];
            if ($lexer->isNextToken(TokenType::T_COMMA)) {
                while ($lexer->isNextToken(TokenType::T_COMMA)) {
                    $parser->match(TokenType::T_COMMA);
                    $parameter = $parser->Literal();
                    $parameters[] = $parameter->value;
                }
            }
            $parser->match(TokenType::T_CLOSE_PARENTHESIS);
            $type .= '('.\implode(', ', $parameters).')';
        }

        $this->targetType = $type;

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf('cast(%s as %s)', $this->sourceType->dispatch($sqlWalker), $this->targetType);
    }
}
