<?php


namespace IsaEken\NetworkMonitor;


use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NetworkInterface
{
    /**
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    private static function humanFileSize($bytes, int $decimals = 2): string
    {
        $size   = [' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return  sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * @param string $contents
     * @return string|float|int
     */
    private static function formatContents(string $contents): float|int|string
    {
        if (is_numeric($contents)) {
            return static::humanFileSize(($contents == (int) $contents) ? (int) $contents : (float) $contents);
        }

        return $contents;
    }

    /**
     * @param string $key
     * @return string
     */
    private static function readableKey(string $key): string
    {
        return ucwords(Str::of($key)->snake()->replace('_', ' ')->replace([
            'rx',
            'tx',
        ], [
            'Incoming',
            'Upcoming',
        ]));
    }

    /**
     * NetworkInterface constructor.
     *
     * @param string $directory
     */
    public function __construct(
        public string $directory
    )
    {
        //
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Str::of($this->directory)->afterLast(DIRECTORY_SEPARATOR)->__toString();
    }

    /**
     * @return Collection
     */
    public function statistics(): Collection
    {
        $instant = $this->instant();
        $statistics = collect([
            'Download size of last 5 minutes' => $instant->download,
            'Upload size of last 5 minutes' => $instant->upload,
        ]);

        collect(glob($this->directory . '/statistics/*'))->each(function ($file) use ($statistics) {
            if (! is_file($file)) {
                return;
            }

            $name = static::readableKey(Str::of($file)->afterLast(DIRECTORY_SEPARATOR)->__toString());
            $contents = static::formatContents(Str::of(file_get_contents($file))->trim()->__toString());

            $statistics->put($name, $contents);
        });

        return $statistics;
    }

    /**
     * @return object
     */
    public function instant(): object
    {
        $object = (object) [
            'download' => '-',
            'upload' => '-',
        ];

        try {
            $data = json_decode(exec(NetworkMonitor::vnstat() . ' --json -i ' . $this->getName()))->interfaces[0]->traffic;
            $item = collect($data->fiveminute)->reverse()->first();
            $object->download = static::humanFileSize($item->rx);
            $object->upload = static::humanFileSize($item->tx);
        } catch (Exception $e) {

        }

        return $object;
    }
}
