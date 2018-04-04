<?php
namespace Traits;

use Slim\Views\PhpRenderer;

trait RendererTrait
{
    /**
     * @var PhpRenderer
     */
    private $renderer;

    public function getRenderer(): ?PhpRenderer
    {
        return $this->renderer;
    }

    protected function render($bodyContent, $template = 'template.phtml')
    {
        if ($this->renderer !== null) {
            return $this->getRenderer()->render(
                $this->response,
                $template,
                [
                    'body' => $bodyContent
                ]
            );
        }
        //@TODO: throw & catch exception
    }
}
