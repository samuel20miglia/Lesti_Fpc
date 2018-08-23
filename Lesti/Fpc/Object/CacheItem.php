<?php
declare(strict_types=1);

namespace Lesti\Fpc\Object;

/**
 *
 * @author samuel vegans
 *
 */
class CacheItem
{
    /**
     * @var string
     */
    private $_content;

    /**
     * @var int
     */
    private $_time;

    /**
     * @var string
     */
    private $_contentType;

    /**
     * @param string $content
     * @param int    $time
     * @param string $contentType
     */
    public function __construct(string $content, int $time, string $contentType)
    {
        $this->_content = $content;
        $this->_time = $time;
        $this->_contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->_content;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->_time;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->_contentType;
    }
}
