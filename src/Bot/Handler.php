<?php
declare(strict_types=1);
namespace Bot;

class Handler
{
    /** @var \Memcached */
    protected $cache;

    /** @var array */
    protected $services;

    public $comparableCommands = [];

    protected $default_method = 'catcherDefault';
    protected $default_exception = 'errorDefault';

    public function execute()
    {
        $this->cache = $this->getCacheInstance();

        if ($this->services) {
            // @todo
            foreach ($this->services as $service => $token) {
                return (new $service)->token($token)->execute($this);
            }
        }

        return null;
    }

    public function onCatcher(string $service_name, string $chat_id, array $data)
    {
        $handler_name = $this->getHandlerName();
        $active_catcher = $this->cache->get(sprintf('Bot:Catcher:%s:%s:%s', $handler_name, $service_name, $chat_id));

        if ($active_catcher) {
            $catcher_method = str_replace('command', 'catcher', $active_catcher);

            if (method_exists($this, $catcher_method)) {
                return $this->{$catcher_method}($service_name, $chat_id, $data);
            }
        }

        if (method_exists($this, $this->default_method)) {
            return $this->{$this->default_method}($service_name, $chat_id, $data);
        }

        return null;
    }

    public function onError(string $service_name, string $chat_id, \Exception $exception)
    {
        if (method_exists($this, $this->default_exception)) {
            return $this->{$this->default_exception}($service_name, $chat_id, $exception);
        }

        return null;
    }

    public function startCatcher(string $service_name, string $chat_id, int $expires_seconds = 3600)
    {
        $handler_name = $this->getHandlerName();
        $method_name = debug_backtrace()[1]['function'];

        if ($handler_name && $method_name) {
            $this->cache->set(sprintf('Bot:Catcher:%s:%s:%s', $handler_name, $service_name, $chat_id), $method_name, $expires_seconds);
        }
    }

    public function stopCatcher(string $service_name, string $chat_id)
    {
        $handler_name = $this->getHandlerName();
        $method_name = debug_backtrace()[1]['function'];

        if ($handler_name && $method_name) {
            $this->cache->delete(sprintf('Bot:Catcher:%s:%s:%s', $handler_name, $service_name, $chat_id));
        }
    }

    public function statusCatcher(string $service_name, string $chat_id)
    {
        $handler_name = $this->getHandlerName();
        $cache = $this->cache->get(sprintf('Bot:Catcher:%s:%s:%s', $handler_name, $service_name, $chat_id));

        if ($cache) {
            return sprintf('Catcher «%s» запущен', $cache);
        }

        return 'Catcher остановлен';
    }

    public function getCacheInstance(): \Memcached
    {
        if (!$this->cache) {
            $this->cache = new \Memcached();
            $this->cache->addServer('localhost', 11211);
        }

        return $this->cache;
    }

    public function enabled(string $chat_id, string $service_name): void
    {
        $settings = new Settings($chat_id, $service_name);
        $settings->setIsDisabled(false)->save();
    }

    public function disabled(string $chat_id, string $service_name): void
    {
        $settings = new Settings($chat_id, $service_name);
        $settings->setIsDisabled(true)->save();
    }

    public function getHandlerName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}