<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace mopon\apidoc\components;


use yii\base\InlineAction;
/**
 * Description of baseController
 *
 * @author horizon
 */
trait BaseApidoc
{
    /**
     * Returns the first line of docblock.
     *
     * @param \Reflector $reflection
     * @return string
     */
    protected function parseDocCommentSummary($reflection)
    {
        $docLines = preg_split('~\R~u', $reflection->getDocComment());
        if (isset($docLines[1])) {
            return trim($docLines[1], "\t *");
        }
        return '';
    }
    
    /**
     * Returns full description from the docblock.
     *
     * @param \Reflector $reflection
     * @return string
     */
    protected function parseDocCommentDetail($reflection)
    {
        $comment = strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($reflection->getDocComment(), '/'))), "\r", '');
        if (preg_match('/^\s*@\w+/m', $comment, $matches, PREG_OFFSET_CAPTURE)) {
            $comment = trim(substr($comment, 0, $matches[0][1]));
        }
        return $comment;
    }
    
    /**
     * Parses the comment block into tags.
     * @param \Reflector $reflection the comment block
     * @return array the parsed tags
     */
    protected function parseDocCommentTags($reflection)
    {
        $comment = $reflection->getDocComment();
        $comment = "@description \n" . strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))), "\r", '');
        $parts = preg_split('/^\s*@/m', $comment, -1, PREG_SPLIT_NO_EMPTY);
        $tags = [];
        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(.*)/ms', trim($part), $matches)) {
                $name = $matches[1];
                if (!isset($tags[$name])) {
                    $tags[$name] = trim($matches[2]);
                } elseif (is_array($tags[$name])) {
                    $tags[$name][] = trim($matches[2]);
                } else {
                    $tags[$name] = [$tags[$name], trim($matches[2])];
                }
            }
        }
        return $tags;
    }
    
    
    /**
     * Returns help information for this controller.
     *
     * You may override this method to return customized help.
     * The default implementation returns help information retrieved from the PHPDoc comment.
     * @return string
     */
    public function getHelp()
    {
        return $this->parseDocCommentDetail(new \ReflectionClass($this));
    }
    
    /**
     * Returns one-line short summary describing this controller.
     *
     * You may override this method to return customized summary.
     * The default implementation returns first line from the PHPDoc comment.
     *
     * @return string
     */
    public function getHelpSummary()
    {
        return $this->parseDocCommentSummary(new \ReflectionClass($this));
    }
    
    /**
     * Returns the detailed help information for the specified action.
     * @param Action $action action to get help for
     * @return string the detailed help information for the specified action.
     */
    public function getActionHelp($action)
    {
        return $this->parseDocCommentDetail($this->getActionMethodReflection($action));
    }
    
    /**
     * Returns the help information for the anonymous arguments for the action.
     * The returned value should be an array. The keys are the argument names, and the values are
     * the corresponding help information. Each value must be an array of the following structure:
     *
     * - required: boolean, whether this argument is required.
     * - type: string, the PHP type of this argument.
     * - default: string, the default value of this argument
     * - comment: string, the comment of this argument
     *
     * The default implementation will return the help information extracted from the doc-comment of
     * the parameters corresponding to the action method.
     *
     * @param Action $action
     * @return array the help information of the action arguments
     */
    public function getActionArgsHelp($action)
    {
        $method = $this->getActionMethodReflection($action);
        $tags = $this->parseDocCommentTags($method);
        $params = isset($tags['param']) ? (array) $tags['param'] : [];

        $args = [];

        /** @var \ReflectionParameter $reflection */
        foreach ($method->getParameters() as $i => $reflection) {
            $name = $reflection->getName();
            $tag = isset($params[$i]) ? $params[$i] : '';
            if (preg_match('/^(\S+)\s+(\$\w+\s+)?(.*)/s', $tag, $matches)) {
                $type = $matches[1];
                $comment = $matches[3];
            } else {
                $type = null;
                $comment = $tag;
            }
            if ($reflection->isDefaultValueAvailable()) {
                $args[$name] = [
                    'required' => false,
                    'type' => $type,
                    'default' => $reflection->getDefaultValue(),
                    'comment' => $comment,
                ];
            } else {
                $args[$name] = [
                    'required' => true,
                    'type' => $type,
                    'default' => null,
                    'comment' => $comment,
                ];
            }
        }
        return $args;
    }
    
    /**
     * Returns a one-line short summary describing the specified action.
     * @param Action $action action to get summary for
     * @return string a one-line short summary describing the specified action.
     */
    public function getActionHelpSummary($action)
    {
        return $this->parseDocCommentSummary($this->getActionMethodReflection($action));
    }
    
    private $_reflections = [];

    /**
     * @param Action $action
     * @return \ReflectionMethod
     */
    protected function getActionMethodReflection($action)
    {
        if( !isset($this->_reflections[$action->id]) ) {
            if ($action instanceof InlineAction) {
                $this->_reflections[$action->id] = new \ReflectionMethod($this, $action->actionMethod);
            } else {
                $this->_reflections[$action->id] = new \ReflectionMethod($action, 'run');
            }
        }
        return $this->_reflections[$action->id];
    }
}
