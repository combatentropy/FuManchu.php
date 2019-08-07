<?php

class FuManchu
{
    private $dir;

    public function __construct(array $options = null)
    {
        $this->dir = $options['dir'] ?? 'tpl';
        $this->esc = $options['esc'] ?? true;
    }

    private function excavate(string $template, string $dir)
    {
        $pattern = '/\{\s*\+\s*([^} ]+)\s*\}/';  // {+file}
        $template = preg_replace_callback(
            $pattern,
            function ($matches) use ($pattern, $dir) {
                $file = $matches[1];
                $file = $dir . DIRECTORY_SEPARATOR . $file;
                if (! is_readable($file)) { return ''; }
                $file = realpath($file);
                $template = file_get_contents($file);
                if (preg_match($pattern, $template)) {
                    $dir = dirname($file);
                    $template = $this->excavate($template, $dir);
                }
                return $template;
            },
            $template
        );

        return $template;
    }

    private function translate(string $tpl)
    {
        $dir = '.';

        if ( // If template is a file name instead of a string
            ! preg_match('/\{|\n/', $tpl) and
            strlen($tpl) < 256 and
            is_readable($tpl)
        ) {
            $tpl = realpath($tpl);
            $dir = dirname($tpl);
            $tpl = file_get_contents($tpl);
        }

        $tpl = $this->excavate($tpl, $dir);

        // {else}
        $tpl = preg_replace('/\{\s*else\s*\}/', '<?php else: ?>', $tpl);

        // {/if}
        $tpl = preg_replace('/\{\s*\/\s*if\s*\}/', '<?php endif ?>', $tpl);

        // {/each}
        $tpl = preg_replace('/\{\s*\/\s*each\s*\}/', '<?php endforeach ?>', $tpl);


        // Reusable patterns
        $var_reg = '[A-Za-z_][A-Za-z0-9_.]*';
        $scalar_var_reg = '[A-Za-z_][A-Za-z0-9_]*';
        $translate_var = function($var) {
            return '$' . preg_replace('/\.([^.]+)/', "['$1']", $var);
        };

        // {each arr as var}
        $reg = "/\{\s*each\s+($var_reg)\s+as\s+($scalar_var_reg)\s*\}/";
        $tpl = preg_replace_callback(
            $reg,
            function ($matches) use ($translate_var) {
                $arr = $translate_var($matches[1]);
                $var = '$' . $matches[2];
                $tpl = '<?php foreach (' . $arr . ' as ' . $var . '): ?>';
                return $tpl;
            },
            $tpl
        );

        // {var}, {if var}, {else if var}
        $reg = "/\{\s*(((else +)?if\s+)?($var_reg))\s*\}/";
        $tpl = preg_replace_callback(
            $reg,
            function ($matches) use ($translate_var) {
                $ctl = $matches[2];
                $var = $translate_var($matches[4]);
                if ($ctl) {
                    // Change "else if" to "elseif"
                    $ctl = str_replace(' ', '', $ctl);
                    $tpl = '<?php ' . $ctl . '(' . $var . '): ?>';
                } else if ($this->esc) {
                    $tpl = '<?= htmlspecialchars(' . $var . ', ENT_QUOTES | ENT_HTML5) ?>';
                } else {
                    $tpl = '<?= ' . $var . ' ?>';
                }
                return $tpl;
            },
            $tpl
        );

        // {no_esc var}
        $reg = "/\{\s*no_esc\s+($var_reg)\s*\}/";
        $tpl = preg_replace_callback(
            $reg,
            function ($matches) use ($translate_var) {
                $var = $translate_var($matches[1]);
                $tpl = '<?= ' . $var . ' ?>';
                return $tpl;
            },
            $tpl
        );

        // Preserve intended indentation
        $tpl = preg_replace('/^\s+(<\?.+?\?>)$/m', '$1', $tpl);

        return $tpl;
    }

    private function run(array $_DATA, string $_TEMPLATE)
    {
        extract($_DATA);
        eval('?>' . $_TEMPLATE);
    }

    public function render($arg1 = null, $arg2 = null)
    {
        // Determine data and template
        
        $tpl = '';
        $data = [];
        $caller = $_SERVER['SCRIPT_NAME'] ?? $argv[0];

        if (is_string($arg1)) {
            $tpl = $arg1;
            if (is_array($arg2)) { $data = $arg2; }
        } elseif (is_array($arg1)) {
            $data = $arg1;
            if (is_string($arg2)) { $tpl = $arg2; }
        }

        if (! $tpl) {
            $tpl = pathinfo($caller, PATHINFO_BASENAME);
            $tpl = $this->dir . DIRECTORY_SEPARATOR . $tpl;
        }

        if (! $data) {
            foreach ($GLOBALS as $k => $v) {
                if ('GLOBALS' !== $k) { $data[$k] = $v; }
            }
        }

        $tpl = $this->translate($tpl);
        $this->run($data, $tpl);
    }
}
