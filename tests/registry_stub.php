<?php

/** Minimal stand-in for Joomla's Registry (only what the helpers use). */

namespace Joomla\Registry {
    // Minimal stand-in for Joomla's Registry (only what the helpers use).
    class Registry
    {
        public function __construct(private array $d = [])
        {
        }

        public function get(string $k, $default = null)
        {
            return $this->d[$k] ?? $default;
        }

        public function set(string $k, $v): void
        {
            $this->d[$k] = $v;
        }

        public function toArray(): array
        {
            return $this->d;
        }
    }
}
