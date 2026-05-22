<?php

namespace LearnAcademy\App;

class View
{
    private string $viewsDir;
    private array  $sharedData = [];

    public function __construct(string $viewsDir)
    {
        $this->viewsDir = rtrim($viewsDir, '/\\');
    }

    public function share(array $data): void
    {
        $this->sharedData = array_merge($this->sharedData, $data);
    }

    /**
     * Render a view and return HTML string.
     *
     * @param string $view  e.g. 'auth/login', 'course/detail'
     * @param array  $data  Variables to inject
     */
    public function render(string $view, array $data = []): string
    {
        $file = $this->viewsDir . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $file");
        }

        $vars = array_merge($this->sharedData, $data);
        extract($vars);

        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Render a view and send it to output directly.
     */
    public function output(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }

    /**
     * Render a view inside the main layout.
     */
    public function layout(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $content = $this->render($view, $data);
        $this->output($layout, array_merge($data, ['content' => $content]));
    }
}
