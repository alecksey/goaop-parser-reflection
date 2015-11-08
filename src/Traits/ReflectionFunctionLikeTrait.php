<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2015, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParserReflection\Traits;


use ParserReflection\NodeVisitor\GeneratorDetector;
use ParserReflection\NodeVisitor\StaticVariablesCollector;
use ParserReflection\ReflectionException;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;

/**
 * General trait for all function-like reflections
 */
trait ReflectionFunctionLikeTrait
{
    use InitializationTrait;

    /**
     * @var FunctionLike
     */
    protected $functionLikeNode;

    /**
     * Namespace name
     *
     * @var string
     */
    protected $namespaceName = '';

    /**
     * @var array|ReflectionParameter
     */
    protected $parameters = [];

    /**
     * {@inheritDoc}
     */
    public function getClosureScopeClass()
    {
        $this->initializeInternalReflection();

        return forward_static_call('parent::getClosureScopeClass');
    }

    /**
     * {@inheritDoc}
     */
    public function getClosureThis()
    {
        $this->initializeInternalReflection();

        return forward_static_call('parent::getClosureThis');
    }

    public function getDocComment()
    {
        return $this->functionLikeNode->getDocComment();
    }

    public function getEndLine()
    {
        return $this->functionLikeNode->getAttribute('endLine');
    }

    public function getExtension()
    {
        return null;
    }

    public function getExtensionName()
    {
        return '';
    }

    public function getFileName()
    {
        return $this->functionLikeNode->getAttribute('fileName');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        if ($this->functionLikeNode instanceof Function_ || $this->functionLikeNode instanceof ClassMethod) {
            $functionName = $this->functionLikeNode->name;

            return $this->namespaceName ? $this->namespaceName . '\\' . $functionName : $functionName;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName()
    {
        return $this->namespaceName;
    }

    public function getNumberOfParameters()
    {
        return count($this->functionLikeNode->getParams());
    }

    public function getParameters()
    {
        if (!isset($this->parameters)) {
            $parameters = [];

            foreach ($this->functionLikeNode->getParams() as $parameterNode) {
                $parameters[] = new ReflectionParameter(
                    $this->getName(),
                    $parameterNode->name,
                    $parameterNode
                );
            }

            $this->parameters = $parameters;
        }

        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName()
    {
        if ($this->functionLikeNode instanceof Function_ || $this->functionLikeNode instanceof ClassMethod) {
            return $this->functionLikeNode->name;
        }

        return false;
    }

    public function getStartLine()
    {
        return $this->functionLikeNode->getAttribute('startLine');
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticVariables()
    {
        $nodeTraverser      = new NodeTraverser(false);
        $variablesCollector = new StaticVariablesCollector($this);
        $nodeTraverser->addVisitor($variablesCollector);

        /* @see https://github.com/nikic/PHP-Parser/issues/235 */
        $nodeTraverser->traverse($this->functionLikeNode->getStmts() ?: array());

        return $variablesCollector->getStaticVariables();
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace()
    {
        return !empty($this->namespaceName);
    }

    /**
     * {@inheritDoc}
     */
    public function isClosure()
    {
        return $this->functionLikeNode instanceof Closure;
    }

    /**
     * {@inheritDoc}
     */
    public function isDeprecated()
    {
        // userland method/function/closure can not be deprecated
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isGenerator()
    {
        $nodeTraverser = new NodeTraverser(false);
        $nodeDetector  = new GeneratorDetector();
        $nodeTraverser->addVisitor($nodeDetector);

        /* @see https://github.com/nikic/PHP-Parser/issues/235 */
        $nodeTraverser->traverse($this->functionLikeNode->getStmts() ?: array());

        return $nodeDetector->isGenerator();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal()
    {
        // never can be an internal method
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined()
    {
        // always defined by user, because we parse the source code
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isVariadic()
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function returnsReference()
    {
        return $this->functionLikeNode->returnsByRef();
    }
}