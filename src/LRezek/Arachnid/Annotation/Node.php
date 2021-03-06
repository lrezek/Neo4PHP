<?php
/**
 * Contains the annotation class for the Node annotation. This specifies that the class the annotation is applied to is
 * a node, and should contain all the required node annotations (Auto).
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Annotation;

/**
 * Annotation class for the <code>@OGM\Node</code> annotation.
 *
 * @package Arachnid
 * @subpackage Annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class Node
{
    /** @var string Allows you to set the repository class in the class annotation. */
    public $repositoryClass;
}

