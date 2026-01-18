<?php

namespace DLCore\Compilers;

use DLCore\Auth\DLAuth;


/**
 * Parsea las plantillas definidas en el directorio resources.
 * 
 * @package DLCore
 * 
 * @author David E Luna M <davidlunamontilla@gmail.com>
 * @license MIT
 * @version v0
 */
class DLTemplate {
    /**
     * Instancia de la clase DLTemplate
     *
     * @var self|null
     */
    private static ?self $instance = NULL;

    private function __construct() {
    }

    /**
     * Dobles llaves, que serán reemplazadas por entidades PHP.
     *
     * @param string $string_template
     * @return string
     */
    private static function keys(string $string_template): string {
        $search = '/\{\{ \$(.*?) \}\}/';
        $replace = '<?= htmlspecialchars(print_r($$1, true)); ?>';

        return preg_replace($search, $replace, $string_template);
    }

    /**
     * Devuelve una la función `json_encode` con los parámetros establecidos
     * para que puedas devolver a partir de un Array una cadena JSON formateada.
     *
     * @param string $string_template
     * @return string
     */
    private static function convert_string_array_to_json_pretty(string $string_template): string {
        $search = '/@json\((.*?),?\s?(\'|\")pretty(\'|\")\)/';
        $replace = '<?= json_encode($1, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>';
        return preg_replace($search, $replace, $string_template);
    }

    /**
     * Devuelve una función `json_encode` con un $array pasado como argumento
     *
     * @param string $string_template Cadena a ser procesada
     * @return string
     */
    private static function convert_string_array_to_json(string $string_template): string {
        $search = '/@json\((.*?)\)/';
        $replace = '<?= json_encode($1); ?>';
        return preg_replace($search, $replace, $string_template);
    }

    /**
     * Una llave de apertura y cierre con dos (02) signos de admiración
     * de cierre que serán reemplazadas por entidades PHP sin filtros.
     *
     * @param string $string_template Código string_template de la plantilla con sus directivas
     * @return string
     */
    private static function keys_html(string $string_template): string {
        $search = '/\{\!\! \$(.*?) \!\!\}/m';
        $replace = '<?= print_r(trim($$1), true); ?>';

        return preg_replace($search, $replace, $string_template);
    }

    /**
     * Parsear las directivas de las estructuras condicionales
     *
     * @param string $string_template
     * @return string
     */
    private static function parser_condicionals(string $string_template): string {
        $conditionals_open = '/@if{1}.*\n*$/mi';
        $condicionals_close = '/\@endif|\@endif\n$/mi';
        $else = '/(@else)+\s*if+\s*/m';

        $string_template = trim($string_template);

        $string_template = preg_replace_callback($conditionals_open, function (array $matches) {
            $found = $matches[0];
            $if = trim(trim($found, "@"));

            $if = "<?php $if { ?>";
            return trim($if);
        }, $string_template);

        $string_template = preg_replace_callback($condicionals_close, function (array $matches) {
            $found = $matches[0];
            $endif = str_replace($found, "<?php } ?>", $found);
            return trim($endif);
        }, $string_template);

        $string_template = self::parse_else($string_template);
        $string_template = self::parse_else_if($string_template);

        return $string_template;
    }

    /**
     * Parsea la estructura else
     *
     * @param string $string_template
     * @return string
     */
    public static function parse_else(string $string_template): string {
        $pattern = '/@else{1}$/';
        $replace = "<?php } else { ?>";

        $lines = preg_split("/\n/", $string_template);
        $newLines = [];

        foreach ($lines as $key => $line) {
            $newLine = preg_replace($pattern, $replace, trim($line));
            array_push($newLines, $newLine);
        }

        return implode("\n", $newLines);
    }

    /**
     * Parsea la estructura elseif
     *
     * @param string $string_template
     * @return string
     */
    public static function parse_else_if(string $string_template): string {
        $pattern = '/@(else\s*if)\s*(.*)?\)/';
        $replace = "<?php } $1 $2) { ?>";

        $lines = preg_split("/\n/", $string_template);
        $newLines =  [];

        foreach ($lines as $key => $line) {
            $newLine = preg_replace($pattern, $replace, trim($line));
            array_push($newLines, $newLine);
        }

        return implode("\n", $newLines);
    }

    /**
     * Parsea una estructura repetitida utiliza para
     * iterar arrays
     *
     * @param string $string_template
     * @return string
     */
    private static function make_foreach(string $string_template): string {
        $find_for = '/\@foreach.*\n*/mi';
        $endfor = '/\@endforeach.*\n*/mi';


        $string_template = preg_replace_callback($find_for, function (array $matches) {
            $found = $matches[0];
            $for = trim(trim($found, '@'));

            $php = "<?php $for: ?>";
            return trim($php);
        }, $string_template);

        $string_template = preg_replace_callback($endfor, function (array $matches) {
            $found = $matches[0];
            $end = trim(trim($found, '@'));

            $php = "<?php $end; ?>";
            return trim($php);
        }, $string_template);


        return trim($string_template);
    }

    /**
     * Itera estructuras repetitivas
     *
     * @param string $string_template
     * @return string
     */
    private static function makefor(string $string_template): string {
        $findFor = '/\@for.*\n*/mi';
        $endfor = '/\@endfor.*\n*/mi';


        $string_template = preg_replace_callback($findFor, function (array $matches) {
            $found = $matches[0];
            $for = trim(trim($found, '@'));

            $php = "<?php $for: ?>";
            return trim($php);
        }, $string_template);

        $string_template = preg_replace_callback($endfor, function (array $matches) {
            $found = $matches[0];
            $end = trim(trim($found, '@'));

            $php = "<?php $end; ?>";
            return trim($php);
        }, $string_template);


        return trim($string_template);
    }

    /**
     * Crea etiquetas de apertura y cierre de PHP a partir
     * de las directivas @php y @endphp de Laravel
     *
     * @param string $string_template
     * @return string
     */
    private static function make_php(string $string_template): string {
        $string_template = preg_replace("/\@php/", "<?php", $string_template);
        $string_template = preg_replace("/\@endphp/", "?>", $string_template);

        return trim($string_template);
    }


    /**
     * Compila las plantillas dl-template a PHP
     *
     * @param string $string_template
     * @return string
     */
    public static function build(string $string_template): string {
        $string_template = self::parseComments($string_template);

        $string_template = self::keys($string_template);
        $string_template = self::keys_html($string_template);
        $string_template = self::parser_condicionals($string_template);
        $string_template = self::make_foreach($string_template);
        $string_template = self::makefor($string_template);
        $string_template = self::make_php($string_template);
        $string_template = self::convert_string_array_to_json_pretty($string_template);
        $string_template = self::convert_string_array_to_json($string_template);
        $string_template = self::parse_includes($string_template);
        $string_template = self::parse_print($string_template);
        $string_template = self::generate_token_csrf(string_template: $string_template);
        $string_template = self::parse_markdown($string_template);
        
        $string_template = self::parser_functions($string_template);
        $string_template = self::parse_break($string_template);
        $string_template = self::parse_continue($string_template);
        $string_template = self::parse_var($string_template);
    
        return $string_template;
    }

    /**
     * Alias de get_instance
     * 
     * @deprecated Este método se eliminará en la versión v2.0.0
     *
     * @return self
     */
    public static function getInstance(): self {
        return self::get_instance();
    }

    /**
     * Devuelve la instancia de DLTemplate. Se genera una instancia nueva si previamente
     * no ha sido instanciada.
     *
     * @return self
     */
    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Permite parsear la directiva que permite extender la vista base
     *
     * @param string $string_template
     * @param array $data
     * @return string
     */
    public static function parse_directive(string $string_template, array $data = []): string {
        $search = '@base(\'home\')';
        $search = "/@base\((.*)?\)/";

        $replace = '<?php DLCore\Compilers\DLView::load($1, $varnames); ?>';

        preg_match_all($search, $string_template, $matches);
        $string_template = preg_replace($search, "", $string_template);
        $string_template = self::parse_sections($string_template);

        /**
         * Rutas incluidas a partir de directivas principales
         * 
         * @var array
         */
        $includes = [];

        foreach ($matches[0] as $key => $value) {
            $string = preg_replace($search, $replace, $value);
            array_push($includes, $string);
        }


        $string_template .= "\n\n" . implode("\n", $includes);
        return $string_template;
    }

    /**
     * Parsea las secciones de las plantillas y las convierte en variables
     * con contenido establecidos en dichas secciones.
     * 
     *
     * @param string $string_template
     * @return string
     */
    private static function parse_sections(string $string_template): string {
        $pattern = '/@section\((.*?)\)\s*([\s\S]*?)\s*@endsection/';

        preg_match_all($pattern, $string_template, $matches);
        $string_template = preg_replace($pattern, "", $string_template);

        /**
         * Bloque de secciones
         * 
         * @var array
         */
        $blocks = $matches[0];

        $newBlocks = [];

        foreach ($blocks as $key => $block) {
            $block = trim($block);
            if (empty($block)) continue;

            $pattern = '/@section\((.*?)\)/';
            $replace = "<?php ob_start(); ?>";

            preg_match($pattern, $block, $matches);
            $s1 = $matches[1] ?? '';
            $s1 = str_replace("'", "", $s1);
            $s1 = trim($s1);

            if (empty($s1)) continue;

            $block = preg_replace($pattern, $replace, $block);

            $pattern = '/@endsection/';
            $replace = "<?php \$$s1 = ob_get_clean(); ?>\n<?php \$data['$s1'] = $$s1; ?>\n";

            $block = preg_replace($pattern, $replace, $block);

            array_push($newBlocks, $block);
        }

        $string = implode("", $newBlocks);

        $string = trim($string);
        $string_template = trim($string_template);

        return $string . $string_template;
    }

    /**
     * Parsea la directa `@includes`
     *
     * @param string $string_template
     * @return string
     */
    public static function parse_includes(string $string_template): string {
        $pattern = "/@includes\((.*?)\)/";
        $replace = "<?php DLCore\Compilers\DLView::load($1, \$varnames); ?>";

        $string_template = preg_replace($pattern, $replace, $string_template);
        return $string_template;
    }

    /**
     * Parsea la directiva @print
     *
     * @param string $string_template
     * @return string
     */
    public static function parse_print(string $string_template): string {
        $pattern = "/@print\((.*?)\)/";

        $string_template = preg_replace_callback($pattern, function ($matches) use ($string_template) {
            $m1 = $matches[1] ?? '';

            $m1 = trim($m1, '\'');
            $m1 = trim($m1);

            $section = $m1;
            $m1 = preg_replace("/\s/", "_", $m1);

            return empty(trim($m1))
                ? ''
                : "<?php if (!isset($$m1)) {echo \"<h3 style=\\\"color: white; background-color: #d00000; padding: 20px; border-radius: 5px; font-weight: normal\\\">No existe las sección <strong style=\\\"padding: 10px; border-radius: 5px; background-color: #000000a0\\\">$section</strong></h3>\"; http_response_code(500); exit(1);} print_r($$m1); ?>";
        }, $string_template);

        return trim($string_template);
    }

    /**
     * Elimina todos los comentarios. La estructura de
     * comentarios es la siguiente:
     * 
     * ```
     * {{-- ... --}}
     *```
     *
     * @param string $string_template
     * @return string
     */
    public static function parseComments(string $string_template): string {
        $pattern = "/\{\{\-\-([\s\S]*?)\-\-\}\}/";
        $string_template = preg_replace($pattern, "", $string_template);

        $pattern = "/<\!\-\-([\s\S]*?)\-\->/";
        $string_template = preg_replace($pattern, "", $string_template);

        return trim($string_template);
    }

    /**
     * Devuelve un elemento de formulario de tipo `hidden` con un
     * valor que es el token de referencia. 
     *
     * @param string $string_template
     * @return string
     */
    private static function generate_token_csrf(string $string_template): string {
        $string_template = self::generate_token_csrf_with_field($string_template);

        /**
         * Patrón de busca de la directiva @csrf
         * 
         * @var string $pattern
         */
        $pattern = "/@csrf/";

        $auth = DLAuth::get_instance();
        $token = $auth->get_token();

        $replace = "<input type=\"hidden\" name=\"csrf-token\" id=\"csrf-token\" value=\"{$token}\" />";
        $string_template = preg_replace($pattern, $replace, $string_template) ?? $string_template;

        return $string_template;
    }

    /**
     * Permite establecer un nombre personalizado al campo oculto del token CSRF.
     *
     * @param string $stromg_template
     * @return string
     */
    private static function generate_token_csrf_with_field(string $stromg_template): string {
        /**
         * Autenticador del sistema
         * 
         * @var DLAuth $auth
         */
        $auth = DLAuth::get_instance();

        /**
         * Token del sistema
         * 
         * @var string $token
         */
        $token = $auth->get_token();

        /**
         * Patrón de búsqueda de la directiva @csrf
         * 
         * @var string $pattern
         */
        $pattern = '/\@csrf\(\"(.*)\"\)/';

        /**
         * Valor de reemplazo.
         * 
         * @var string
         */
        $replace = "<input type=\"hidden\" name=\"$1\" id=\"$1\" value=\"{$token}\" />";

        $stromg_template = preg_replace($pattern, $replace, $stromg_template);

        $pattern = '/\@csrf\(\'(.*)\'\)/';

        $stromg_template = preg_replace($pattern, $replace, $stromg_template);

        return trim($stromg_template);
    }

    /**
     * Ubica el archivo Markdown y lo compila
     *
     * @param string $string_template
     * @return string
     */
    public static function parse_markdown(string $string_template): string {
        $pattern = "/@markdown\((.*?)\)/";
        $replace = "<?php echo \DLCore\Compilers\DLMarkdown::parse($1); ?>";

        return preg_replace($pattern, $replace, $string_template) ?? $string_template;
    }

    /**
     * Parsea todo lo que no se haya parseado entre llaves (`{{... }}`)
     *
     * @param string $string_template
     * @return string
     */
    public static function parser_functions(string $string_template): string {
        $pattern = '/\{\{\s*(.*?)\s*\}\}/';
        $replace = "<?= $1; ?>";

        return preg_replace($pattern, $replace, $string_template);
    }

    /**
     * Parsea la directiva @continue a <?php continue; ?>
     *
     * @return string
     */
    public static function parse_continue(string $input): string {
        $pattern = self::get_directive_parse("continue");
        $replace = "<?php continue; ?>";

        return preg_replace($pattern, $replace, $input);
    }

    /**
     * Traduce la directiva `@break` a `<?php break; ?>`
     *
     * @return string
     */
    public static function parse_break(string $input): string {
        $pattern = self::get_directive_parse('break');
        return preg_replace($pattern, '<?php break; ?>', $input);
    }

    /**
     * Traduce la directiva `@varname('variable', 'Valor de la variable)` a `$name = "Valor de la variable";
     * 
     * > Advertencia: la directiva `@varname` se encuentra en fase experimental.
     *
     * @param string $input
     * @return string
     */
    public static function parse_var(string $input): string {
        $pattern = "/(?<!\S)(\@varname\(([a-z]+), ((.*?))\))(?!\S)/";
        $replace = "<?php \$$2 = $3; ?>";

        return trim(preg_replace($pattern, $replace, $input));
    }

    /**
     * Devuelve la expresión regular de la directiva
     *
     * @param string $input Nombre de la directiva a ser procesada
     * @return string
     */
    public static function get_directive_parse(string $input): string {
        return "/(?<!\S)\@{$input}(?!\S)/";
    }
}
