<?php

namespace pukoconsole\nodes;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class RemoveFunctionNodes
 * @package pukoconsole\nodes
 */
class RemoveFunctionNodes extends NodeVisitorAbstract
{

    var $function;

    public function __construct($function)
    {
        $this->function = $function;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod) {
            if ($node->name === $this->function) {
                return NodeTraverser::REMOVE_NODE;
            }
        }
        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }

}