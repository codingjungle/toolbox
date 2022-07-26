<?php

/**
 * @brief       ParentVisitor Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.12
 * @version     -storm_version-
 */


namespace IPS\toolbox\Code\Utils;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
\IPS\toolbox\Application::loadAutoLoader();

class _ParentVisitor extends NodeVisitorAbstract {
    private array $parentCalls = [];
    private int $firstLineNum;

    public function __construct($firstLineNum) {
        $this->firstLineNum = $firstLineNum;
    }

    public function enterNode(Node $node) {
        if($node instanceof Node\Expr\StaticCall){
            //_p($node);
        }
        if (
            $node instanceof Node\Expr\StaticCall &&
            $node->class instanceof Node\Name ) {
            $call = ['method' => $node->name->name];
            if ($node->getStartLine() > -1) {
                $call['line'] = $this->firstLineNum + $node->getStartLine() - 1;
            }
            $this->parentCalls[\mb_strtolower($node->name->name)] = $call;
        }
    }

    public function getParentCalls(): array {
        return $this->parentCalls;
    }
}