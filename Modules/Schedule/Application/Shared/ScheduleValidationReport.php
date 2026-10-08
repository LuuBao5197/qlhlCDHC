<?php

namespace Modules\Schedule\Application\Shared;

use Illuminate\Validation\ValidationException;

/**
 * Collects every validation problem found while analysing a semester payload
 * so the user sees the full list in a single submission instead of fixing and
 * resubmitting one error at a time.
 *
 * Messages are grouped by form field key (what Laravel expects) and, when the
 * problem belongs to a class tab, also by class key so the UI can flag the
 * right tab. A class can also be "flagged" without a message when one of its
 * rows was dropped because it was already reported elsewhere; that lets later
 * checks (e.g. month coverage) skip it instead of emitting noisy follow-ups.
 */
class ScheduleValidationReport
{
    /** @var array<string, array<int, string>> */
    private array $messages = [];

    /** @var array<string, array<int, string>> */
    private array $classMessages = [];

    /** @var array<string, true> */
    private array $flaggedClasses = [];

    public function add(string $key, string $message, int|string|null $classKey = null): void
    {
        if (! in_array($message, $this->messages[$key] ?? [], true)) {
            $this->messages[$key][] = $message;
        }

        if ($classKey !== null && $classKey !== '') {
            $classKey = (string) $classKey;
            $this->flaggedClasses[$classKey] = true;

            if (! in_array($message, $this->classMessages[$classKey] ?? [], true)) {
                $this->classMessages[$classKey][] = $message;
            }
        }
    }

    public function flagClass(int|string|null $classKey): void
    {
        if ($classKey !== null && $classKey !== '') {
            $this->flaggedClasses[(string) $classKey] = true;
        }
    }

    public function isClassFlagged(int|string|null $classKey): bool
    {
        return $classKey !== null && isset($this->flaggedClasses[(string) $classKey]);
    }

    /**
     * Runs $callback and records any ValidationException it throws instead of
     * letting it abort the whole analysis. Returns null when it failed.
     */
    public function attempt(callable $callback, int|string|null $classKey = null, string $prefix = ''): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            $this->mergeException($e, $classKey, $prefix);

            return null;
        }
    }

    public function mergeException(ValidationException $e, int|string|null $classKey = null, string $prefix = ''): void
    {
        foreach ($e->errors() as $key => $messages) {
            foreach ((array) $messages as $message) {
                $this->add((string) $key, $prefix . $message, $classKey);
            }
        }
    }

    public function hasErrors(): bool
    {
        return $this->messages !== [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function classMessages(): array
    {
        return $this->classMessages;
    }

    public function throwIfFailed(): void
    {
        if ($this->hasErrors()) {
            throw ValidationException::withMessages($this->messages);
        }
    }
}
