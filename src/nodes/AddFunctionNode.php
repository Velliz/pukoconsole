<?php

namespace pukoconsole\nodes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class AddFunctionNode
 * @package pukoconsole\nodes
 */
class AddFunctionNode extends NodeVisitorAbstract
{

    var $function;

    public function __construct($function)
    {
        $this->function = $function;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if ($node->name === $this->function) {
                $fn = new Node\Stmt\Function_($this->function, array(), array(
                    'id' => ''
                ));
                return $fn;
            }
        }
        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }

}