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
        $keyStorage = $this->getKeyStorage();

        $data = [];
        if ($this->redis->exists($keyStorage)) {
            $data = $this->redis->get($keyStorage);

            if ($data) {
                $data = json_decode($data, true);
            }
        }

        foreach ($this->updateProperties as $key => $value) {
            $data[$key] = $value;
        }

        $this->redis->set($keyStorage, json_encode($data));
    }

    private function getKeyStorage(): string
    {
        return md5(sprintf('Bot:Settings:%s:%s', $this->serviceName, $this->chatId));
    }

    private function fill(): void
    {
        $keyStorage = $this->getKeyStorage();

        if ($this->redis->exists($keyStorage)) {
            $json = json_decode($this->redis->get($keyStorage), true);

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
            $redis = new \Redis();
            $redis->connect('127.0.0.1');

            $this->redis = $redis;
        }

        return $this->redis;
    }
}
