<?php

declare(strict_types=1);

namespace dogit;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;

/**
 * @internal
 */
final class Utility
{
    /**
     * Filters a list of comments by whether they have patches.
     *
     * Comments without patches, and non-patch files are discarded.
     *
     * @param \dogit\DrupalOrg\Objects\DrupalOrgComment[] $comments
     *
     * @return array<int, array{\dogit\DrupalOrg\Objects\DrupalOrgComment, array<int, \dogit\DrupalOrg\Objects\DrupalOrgFile>}>
     */
    public static function filterCommentsWithPatches(DrupalOrgObjectIterator $objectIterator, array $comments): array
    {
        // File type is needed for all files of all comments below, unwrap
        // comments and their files upfront.
        $objectIterator->unstubFiles(
            iterator_to_array(static::getFilesFromComments($objectIterator->unstubComments($comments)), false),
        );

        $return = [];
        foreach ($comments as $comment) {
            $patchFiles = array_filter(
                $comment->getFiles(),
                fn (DrupalOrgFile $file): bool => 'text/x-diff' === $file->getMime(),
            );

            if (0 === count($patchFiles)) {
                continue;
            }

            $return[] = [
                $comment,
                $patchFiles,
            ];
        }

        return $return;
    }

    /**
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $events
     *
     * @return \Generator<int, \dogit\DrupalOrg\Objects\DrupalOrgComment>
     */
    public static function getCommentsFromEvents(array $events): \Generator
    {
        foreach ($events as $event) {
            yield $event->getComment();
        }
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgComment[] $comments
     *
     * @return \Generator<int, \dogit\DrupalOrg\Objects\DrupalOrgFile>
     */
    public static function getFilesFromComments(array $comments): \Generator
    {
        foreach ($comments as $comment) {
            yield from new \ArrayObject($comment->getFiles());
        }
    }

    /**
     * Reduces duplicate object types.
     *
     * @template T of \dogit\DrupalOrg\Objects\DrupalOrgObject
     *
     * @param T[] $objects
     *   Mixed object types may be passed
     *
     * @return T[]
     */
    public static function deduplicateDrupalOrgObjects(array $objects): array
    {
        $registry = [];
        foreach ($objects as $k => $object) {
            if (in_array(
                $object->id(),
                $registry[get_class($object)] ?? [],
                true
            )) {
                unset($objects[$k]);
            }
            $registry[get_class($object)][] = $object->id();
        }

        return $objects;
    }

    /**
     * @param \dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent[] $events
     *   Must be ordered by time ASC
     */
    public static function versionAt(DrupalOrgObjectIterator $objectIterator, \DateTimeImmutable $at, array $events): string
    {
        if (0 === count($events)) {
            throw new \InvalidArgumentException('No events passed');
        }

        // We need the created date type for comments with version change
        // events below. Unwrap the comment upfront.
        $objectIterator->unstubComments(iterator_to_array(Utility::getCommentsFromEvents($events)));

        $lastVersion = null;
        foreach ($events as $event) {
            $version = $event->to();

            // Use the comment created date instead of file date.
            if ($event->getComment()->getCreated() >= $at) {
                break;
            }

            $lastVersion = $version;
        }

        if (!isset($lastVersion)) {
            // This may happen if \dogit\Utility::ensureInitialVersionChange is
            // not called on the event list beforehand.
            // In rare cases the first comment associated in ensureInitialVersionChange has a timestamp
            // after the first patch, such as comments 12659445 12659477 in issue 2981047. In this case
            // just get the date of the first event.
            return reset($events)->to();
        }

        return $lastVersion;
    }

    /**
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $events
     *   Stack of events
     *
     * @return \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[]
     *   New stack of events
     */
    public static function ensureInitialVersionChange(array $events, DrupalOrgIssue $issue): array
    {
        if (0 === count($events)) {
            throw new \InvalidArgumentException('No events passed.');
        }

        $firstVersionChange = null;
        foreach ($events as $event) {
            if ($event instanceof VersionChangeEvent) {
                $firstVersionChange = $event;
                break;
            }
        }

        $allComments = $issue->getComments();
        if (0 === count($allComments)) {
            throw new \InvalidArgumentException('Issue has no comments.');
        }

        $firstComment = reset($allComments);
        if ($firstVersionChange && $firstComment->id() !== $firstVersionChange->getComment()->id()) {
            // Create a status for the first comment based on the *from* of the
            // first status.
            array_unshift($events, new VersionChangeEvent($firstComment, '', $firstVersionChange->from()));
        } elseif (!$firstVersionChange) {
            // Otherwise if there are no status change events on the issue yet,
            // then create one for the first comment with the current issue status.
            array_unshift($events, new VersionChangeEvent($firstComment, '', $issue->getCurrentVersion()));
        }

        return $events;
    }

    /**
     * Build validators which when passed a number return TRUE if the
     * number matches the constraint.
     *
     * @param string[] $constraints
     *
     * @return callable[]
     *
     * @throws \InvalidArgumentException
     */
    public static function numericConstraintRuleBuilder(array $constraints): array
    {
        $filters = [];
        foreach ($constraints as $constraint) {
            // The whole constraint is an int:
            $isInt = fn (string $string): bool => 1 === preg_match('/^\d{1,5}$/m', $string);

            // Verbatim:

            if ($isInt($constraint)) {
                $filters[] = fn (int $number) => $number === (int) $constraint;
            } elseif (str_starts_with($constraint, '<=') && $isInt(substr($constraint, 2))) {
                $filters[] = fn (int $number) => $number <= (int) substr($constraint, 2);
            } elseif (str_starts_with($constraint, '>=') && $isInt(substr($constraint, 2))) {
                $filters[] = fn (int $number) => $number >= (int) substr($constraint, 2);
            } elseif (str_starts_with($constraint, '<') && $isInt(substr($constraint, 1))) {
                $filters[] = fn (int $number) => $number < (int) substr($constraint, 1);
            } elseif (str_starts_with($constraint, '>') && $isInt(substr($constraint, 1))) {
                $filters[] = fn (int $number) => $number > (int) substr($constraint, 1);
            } elseif (str_starts_with($constraint, '!=') && $isInt(substr($constraint, 2))) {
                $filters[] = fn (int $number) => $number !== (int) substr($constraint, 2);
            } elseif (str_starts_with($constraint, '<>') && $isInt(substr($constraint, 2))) {
                $filters[] = fn (int $number) => $number !== (int) substr($constraint, 2);
            } elseif (str_starts_with($constraint, '=') && $isInt(substr($constraint, 1))) {
                $filters[] = fn (int $number) => $number === (int) substr($constraint, 1);
            } else {
                throw new \InvalidArgumentException(sprintf('Unable to process constraint: %s', $constraint));
            }
        }

        return $filters;
    }
}
