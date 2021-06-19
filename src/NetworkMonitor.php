<?php


namespace IsaEken\NetworkMonitor;


use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NetworkMonitor
{
    /**
     * @return bool
     */
    private static function isSupportedOs(): bool
    {
        return Str::of(PHP_OS)->trim()->lower()->contains("linux");
    }

    /**
     * @throws Exception
     */
    private static function throwIfNotSupported(): void
    {
        if (! static::isSupportedOs()) {
            throw new Exception('Your operating system is not supported yet.');
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function vnstat(): string
    {
        $which = exec('which vnstat');
        if ($which === false || mb_strlen($which) < 1) {
            throw new Exception('Please run "sudo apt-get install vnstat"');
        }

        return Str::of($which)->trim()->__toString();
    }

    /**
     * @return Collection
     * @throws Exception
     */
    public static function getInterfaces(): Collection
    {
        static::throwIfNotSupported();

        $directories = collect(glob('/sys/class/net/*'))->filter(function (string $directory) {
            if (! is_dir($directory)) {
                return false;
            }

            if (! is_dir($directory . '/statistics')) {
                return false;
            }

            if (! file_exists($directory . '/statistics/rx_bytes')) {
                return false;
            }

            return true;
        });

        return collect($directories)->map(function (string $directory) {
            return new NetworkInterface($directory);
        });
    }

    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public static function hasInterface(string $name): bool
    {
        /** @var NetworkInterface $interface */
        foreach (static::getInterfaces() as $interface) {
            if ($name === $interface->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @return NetworkInterface
     * @throws Exception
     */
    public static function findInterface(string $name): NetworkInterface
    {
        return static::getInterfaces()->filter(function (NetworkInterface $interface) use ($name) {
            return $interface->getName() === $name;
        })->first();
    }
}
