<?php

declare(strict_types=1);

namespace TwigCsFixer\Sniff;

use TwigCsFixer\Token\Token;

/**
 * Checks that there are not 2 empty lines following each other.
 */
final class EmptyLinesSniff extends AbstractSniff
{
    protected function process(int $tokenPosition, array $tokens): void
    {
        $token = $tokens[$tokenPosition];

        if (!$this->isTokenMatching($token, Token::EOL_TYPE)) {
            return;
        }

        $i = 0;
        while (
            isset($tokens[$tokenPosition - ($i + 1)])
            && $this->isTokenMatching($tokens[$tokenPosition - ($i + 1)], Token::EOL_TYPE)
        ) {
            $i++;
        }

        if (0 === $i || 1 === $i) {
            return;
        }

        $fixer = $this->addFixableError(
            sprintf('More than 1 empty lines are not allowed, found %d', $i),
            $token
        );

        // Only linting currently.
        if (null === $fixer) {
            return;
        }

        $fixer->beginChangeset();
        while ($i > 1) {
            $fixer->replaceToken($tokenPosition - $i, '');
            $i--;
        }
        $fixer->endChangeset();
    }
}
