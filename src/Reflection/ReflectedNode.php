<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs\Reflection;

use Cake\Core\Configure;

abstract class ReflectedNode
{
    public string $name;

    public string $githubLink;

    public array $repoMap = [
        'cakephp4' => 'cakephp',
        'cakephp3' => 'cakephp',
        'elastic' => 'elastic-search',
        'chronos' => 'chronos',
        'queue' => 'queue',
    ];

    /**
     * @param string $qualifiedName Qualified node name
     * @param \Cake\ApiDocs\Reflection\DocBlock $doc Reflected docblock
     * @param \Cake\ApiDocs\Reflection\Context $context Context info
     * @param \Cake\ApiDocs\Reflection\Source $source Source info
     */
    public function __construct(
        public string $qualifiedName,
        public DocBlock $doc,
        public Context $context,
        public Source $source
    ) {
        preg_match('/[^:\\\\]+$/', $qualifiedName, $matches);
        $this->name = $matches[0];

        $basePath = Configure::read('basePath');
        $repo = $this->repoMap[Configure::read('config')];
        $tag = Configure::read('tag');
        if (str_contains($tag, 'origin/')) {
            $tag = str_replace('origin/', '', $tag);
        }

        $repoPath = str_replace($basePath, '', $this->source->path);
        $githubBase = 'https://github.com/cakephp/';
        $githubBase .= sprintf('%s/tree/%s%s', $repo, $tag, $repoPath);
        $githubBase .= sprintf('#L%s-L%s', $this->source->startLine, $this->source->endLine);
        $this->githubLink = $githubBase;
    }
}
