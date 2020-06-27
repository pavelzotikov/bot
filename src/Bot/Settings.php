<?php
declare(strict_types=1);
namespace Bot;

class Settings
{
    /** @var \Redis */
    private $redis;

    private $chatId;
    private $serviceName;

    private $data = [];
    private $updateProperties = [];

    private $isDisabled = false;

    public function __construct(string $chatId, string $serviceName)
    {
        $this->redis = $this->getRedisInstance();

        $this->chatId = $chatId;
        $this->serviceName = $serviceName;

        $this->fill();
    }

    public function setIsDisabled(bool $value): self
    {
        $this->isDisabled = $value;
        $this->updateProperties['isDisabled'] = $value;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function setData(array $value): self
    {
        $this->data = $value;
        $this->updateProperties['data'] = json_encode($value);

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function save(): void
    {
        $key = $this->getKeyStorage();

        $data = [];
        if ($this->redis->exists($key)) {
            $data = $this->redis->get($key);
        }

        foreach ($this->updateProperties as $key => $value) {
            $data[$key] = $value;
        }

        $this->redis->set($key, $data);
    }

    private function getKeyStorage(): string
    {
        return sprintf('Bot:Settings:%s:%s', $this->serviceName, $this->chatId);
    }

    private function fill(): void
    {
        $key = $this->getKeyStorage();

        if ($this->redis->exists($key)) {
            $json = json_decode($this->redis->get($key), true);

            if ($json) {
                foreach ($json as $key => $value) {
                    if ($key === 'data') {
                        $value = json_decode($value, true);
                    }

                    $this->{$key} = $value;
                }
            }
        }
    }

    public function getRedisInstance(): \Redis
    {
        if (!$this->redis) {
            $this->redis = new \Redis();
            $this->redis->connect('localhost', 6379);
        }

        return $this->redis;
    }
}
