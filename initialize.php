<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

if (!function_exists('jnsalles_detect_base_url')) {
    function jnsalles_detect_base_url()
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        // O preview do Plesk adiciona o domínio e o destino HTTPS ao caminho.
        // Ele precisa ter prioridade sobre APP_URL; caso contrário, respostas AJAX
        // redirecionam o cliente para o domínio público durante o teste do preview.
        if ($host !== ''
            && preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)
            && preg_match('#^(/plesk-site-preview/[^/]+/(?:https?|http)/[^/]+/)#i', $requestPath, $match)
        ) {
            $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
            $scheme = $https !== '' && $https !== 'off' && $https !== '0' ? 'https' : 'http';
            return $scheme . '://' . $host . $match[1];
        }

        $configured = trim((string) getenv('APP_URL'));
        if ($configured !== '') {
            return rtrim($configured, '/') . '/';
        }

        if ($host === '' || !preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)) {
            return 'https://setuppro.com.br/';
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $scheme = $https !== '' && $https !== 'off' && $https !== '0' ? 'https' : 'http';
        return $scheme . '://' . $host . '/';
    }
}

$detectedBaseUrl = jnsalles_detect_base_url();
if (!defined('BASE_URL')) {
    define('BASE_URL', $detectedBaseUrl);
}
if (!defined('BASE_REF')) {
    define('BASE_REF', $detectedBaseUrl);
}
if (!defined('base_url')) {
    define('base_url', $detectedBaseUrl);
}
if (!defined('base_app')) {
    define('base_app', str_replace('\\', '/', __DIR__) . '/');
}
if (!defined('BASE_APP')) {
    define('BASE_APP', str_replace('\\', '/', __DIR__) . '/');
}

/* No Plesk, db.local.php fica um nivel acima do diretorio httpdocs. */
$databaseIsConfigured =
    (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD'))
    || (defined('DB_SERVER') && defined('DB_NAME') && defined('DB_USERNAME') && defined('DB_PASSWORD'));

if (!$databaseIsConfigured) {
    // Este projeto usa apenas a configuracao privada do seu proprio diretorio.
    // Nao herdar configuracoes da assinatura ou do site original.
    $privateConfigCandidates = [];
    $privateConfigCandidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db.local.php';

    $privateConfigLoaded = false;
    foreach (array_unique($privateConfigCandidates) as $privateConfigPath) {
        if (is_file($privateConfigPath) && is_readable($privateConfigPath)) {
            require $privateConfigPath;
            $privateConfigLoaded = true;
            break;
        }
    }

    if (!$privateConfigLoaded) {
        http_response_code(503);
        exit('Configuracao privada do banco nao encontrada. Crie db.local.php fora do httpdocs.');
    }
}

if (!defined('DB_HOST') && defined('DB_SERVER')) {
    define('DB_HOST', DB_SERVER);
}
if (!defined('DB_USER') && defined('DB_USERNAME')) {
    define('DB_USER', DB_USERNAME);
}
if (!defined('DB_SERVER') && defined('DB_HOST')) {
    define('DB_SERVER', DB_HOST);
}
if (!defined('DB_USERNAME') && defined('DB_USER')) {
    define('DB_USERNAME', DB_USER);
}

foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $requiredDatabaseConstant) {
    if (!defined($requiredDatabaseConstant)) {
        http_response_code(503);
        exit('Configuracao privada do banco incompleta.');
    }
}

// Falhar antes de abrir qualquer conexao se houver configuracao de outro site.
if (DB_NAME !== 'sallesano_setuppro' || DB_USER !== 'setuppro_app') {
    http_response_code(503);
    exit('Use exclusivamente o banco e o usuario do ambiente SetupPro.');
}

if (!function_exists('jnsalles_admin_password_hash')) {
    function jnsalles_admin_password_hash($password)
    {
        return password_hash((string) $password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('jnsalles_admin_password_verify')) {
    function jnsalles_admin_password_verify($password, $storedHash)
    {
        $storedHash = (string) $storedHash;
        if (password_get_info($storedHash)['algoName'] !== 'unknown') {
            return password_verify((string) $password, $storedHash);
        }
        return preg_match('/^[a-f0-9]{32}$/i', $storedHash)
            && hash_equals(strtolower($storedHash), md5((string) $password));
    }
}

?>
