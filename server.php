<?php

//define('BASEDIR', getopt('', ['path::'])['path']);

if (php_sapi_name() !== "cli") {
   //  exit("CLI MODE ONLY");
}
/** RUN FOR EVER */
set_time_limit(0);

/** ENVIRONMENT */
define('EXT', '.php'); // Rozszerzenie plików źródłowych
define('DOCROOT', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);


/** DIRECTORIES */
$application = 'application';
$modules = 'modules';
$common_modules = 'common';
$system = 'system';
$rpc = 'rpc';

if (!is_dir($application) AND is_dir(DOCROOT . $application))
    $application = DOCROOT . $application;

if (!is_dir($modules) AND is_dir(DOCROOT . $modules))
    $modules = DOCROOT . $modules;

if (!is_dir($common_modules) AND is_dir(DOCROOT . $common_modules))
    $common_modules = DOCROOT . $common_modules;

if (!is_dir($system) AND is_dir(DOCROOT . $system))
    $system = DOCROOT . $system;

if (!is_dir($rpc) AND is_dir(DOCROOT . $rpc))
    $rpc = DOCROOT . $rpc;

/* DEKLARACJA STAŁYCH ŚRODOWISKOWYCH */
define('APPPATH', realpath($application) . DIRECTORY_SEPARATOR);
define('MODPATH', realpath($modules) . DIRECTORY_SEPARATOR);
define('COMMONPATH', realpath($common_modules) . DIRECTORY_SEPARATOR);
define('SYSPATH', realpath($system) . DIRECTORY_SEPARATOR);
define('RPCPATH', realpath($rpc) . DIRECTORY_SEPARATOR);
/* DEALOKACJA ZMIENNYCH TYMCZASOWYCH */
unset(
        $application, $common_modules, $modules, $system, $rpc
);

// Load the core Kohana class
require SYSPATH . 'classes/kohana/core' . EXT;

if (is_file(APPPATH . 'classes/kohana' . EXT)) {
    // Application extends the core
    require APPPATH . 'classes/kohana' . EXT;
} else {
    // Load empty core extension
    require SYSPATH . 'classes/kohana' . EXT;
}

date_default_timezone_set('Europe/Warsaw');
setlocale(LC_ALL, 'pl_PL.utf-8');
spl_autoload_register(array('Kohana', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
    'abstract' => COMMONPATH . 'abstract', // Klasy Abstrakcyjne
    'database' => MODPATH . 'database', // Database access
    'dao' => MODPATH . 'dao', // Data Access Objects
    'websocket' => MODPATH . 'websocket',
    'jsonrpc' => MODPATH . 'jsonrpc',
    'token' => MODPATH . 'token',
    'system' => COMMONPATH . 'system', // Dodatkowe funkcje i helpery dla PHP/Kohana.
));
I18n::lang('en');

Kohana::init_cli();

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH . 'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/* * *
 * SERWER WEB SOCKET
 */

/**  INSTANCE A WEBSOCKET */
Websocket::set(new Websocket_Server);


/** REGISTER JSONRPC "SEND" METHOD */
JsonRpc::setSendCallable(function($socketId, $payload) {
    $userId = Server::getUserIdBySocketId($socketId);
    Websocket::consoleLogOutput($socketId, $userId, $payload);

    Websocket::server()->wsSend($socketId, $payload);
});

/**
 * ON OPEN
 * @param int $socket_id
 */
Websocket::server()->onOpen(function($socketId) {

});

/**
 * ON AUTH
 * @param int $socket_id
 */
Websocket::server()->onAuth(function($socketId, $data) {
    //try {
        $userId = Arr::get($data, 'userId');
        $wsToken = Arr::get($data, 'wsToken');
        $overwrite = Arr::get($data, 'overwrite');
        $user = Server::getUserById($userId);
        $user->reload();

        if (!Server::isUserAuthorized($user, $wsToken)) {
            Websocket::server()->log("[socket: $socketId][user: {$user->get(Model_User::COLUMN_NICKNAME)} ({$userId})] INCORRECT ACCESS CODE.
                 EXPECTED '{$user->get(Model_User::COLUMN_WS_TOKEN)}', GET '{$wsToken}'");
            return false;
        } else {
            Websocket::server()->log("[socket: $socketId][user: {$user->get(Model_User::COLUMN_NICKNAME)} ({$userId})] CORRECT ACCESS CODE");
        }

        if (Server::isUserLoggedIn($userId)) {
            /** Jeśli nie jesteśmy w trybie nadpisywania sesji to wyświetlamy komunikat dający możliwość
             * zalogowania w trybie nadpisywania sesji po czym zrywamy połączenie.
             */
            if (!$overwrite) {

                JsonRpc::client()
                    ->batch()
                    ->notify('server.canDisconnect')
                    ->notify('page-welcome.reconnectDialog', [
                        'message' => "Your account is currently in use. Would you like to drop current connection and login from this device?",
                        'userId' => $userId,
                        'wsToken' => $wsToken
                    ])->send($socketId);
                return false;
            }
            $expiringSocketId = Server::getSocketIdByUserId($userId);

            /** Wyrejestrowanie z aktualnych serwisów */
            Server::setSocketId($expiringSocketId);
            Server::reassignUserSocket($socketId);
            Rpc_Access::resetUserPage($expiringSocketId);

            JsonRpc::client()
                ->batch()
                ->notify('server.canDisconnect')
                ->notify('popup.alert', [
                    'payload' => "You have been disconnected due to parallel access to account you have been using."
                ])->send($expiringSocketId);

            Websocket::server()->wsClose($expiringSocketId);
        } else {
            Server::registerUserAndSocket($userId, $socketId, $user);
        }
        Server::setSocketId($socketId);
        Server::resetUserCode($user);
        Service_Dashboard_General::join();
//    }catch(Exception $e){
//        return false;
//    }
});


/**
 * ON MESSAGE
 * @param int $socket_id
 * @param string $message
 * @param int $messageLength
 * @param bool $binary
 */
Websocket::server()->onMessage(function($socketId, $message, $messageLength, $binary) {
    /* Empty messages are not allowed for WebSocket standard */
    if ($messageLength == 0) {
        Websocket::server()->wsClose($socketId);
        return;
    }

    /* So we can use Server::getSocketId(); inside called functions */
    Server::setSocketId($socketId);

    /** LOG REQUEST AND RESPONSE (for debugging purposes) */
    $userId = Server::getUserId();
    Websocket::consoleLogInput($socketId, $userId, $message);

    try {
        /* Dispatch JSON RPC 2.0 */
        $jsonRpcResponse = JsonRpc::dispatch($message);

        /* If any feedback from JSONRPC ... */
        if (!is_null($jsonRpcResponse)) {
            Websocket::server()->wsSend($socketId, $jsonRpcResponse);
        }

        Websocket::consoleLogOutput($socketId, $userId, $jsonRpcResponse);
    } catch (Exception $e){
            Websocket::consoleLogError(implode(', ',[
                $e->getFile(), $e->getLine(), $e->getMessage()]));
            Websocket::server()->wsClose($socketId);
    }
});

/**
 * ON CLOSE
 * Remove Clients Data, Process the unsubscribe sequences for all user services.
 */
Websocket::server()->onClose(function($socketId, $status) {
    $userId = Server::getUserIdBySocketId($socketId);

    /* So we can use Server::getSocketId(); inside called functions */
    Server::setSocketId($socketId);
    Server::unregister();

    Websocket::server()->log("[socket: $socketId][user: $userId] CLOSING CONNECTION, error code: $status");
});

/** LOAD ROUTES FOR JSON RPC */
JsonRpcRoutes::instance();

/** MAKE SURE  MYSQL  WOND TIMEOUT FOR A WHILE */
try {
    DB::query(null, "set session wait_timeout = " . PHP_INT_MAX)->execute();
} catch (Exception $e){
    //windows max allowed value
    DB::query(null, "set session wait_timeout = " . 2147483)->execute();
}
/** STARTING SERVER */
Websocket::server()->log("[SERVER] STARTING UP");
Websocket::server()->wsStartServer('0.0.0.0', 9300);
