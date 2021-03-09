<?php
// Copyright 2021 Peter Beverloo. All rights reserved.
// Use of this source code is governed by the MIT license, a copy of which can
// be found in the LICENSE file.

declare(strict_types=1);

namespace Anime;

// The Environment class represents the context for the application's data sources, for example to
// allow split data sources based on the hostname.
class Environment {
    // Returns an array with Environment instances for all environments that have been defined in
    // the configuration file. Both valid and invalid environments will be included.
    public static function all(Configuration $configuration): array {
        $hostnames = array_keys($configuration->get('environments'));
        $environments = [];

        foreach ($hostnames as $hostname)
            $environments[] = Environment::createForHostname($configuration, $hostname);

        return $environments;
    }

    // Initializes a new environment for the |$hostname| with the given |$configuration|. An empty,
    // invalid environment will be initialized when the configuration is not available.
    public static function createForHostname(
            Configuration $configuration, string $hostname): Environment {
        if (!preg_match('/^([a-z0-9]+\.?){2,3}/s', $hostname))
            return new Environment(false);  // invalid format for the |$hostname|.

        $settings = $configuration->get('environments/' . $hostname);
        if ($settings === null)
            return new Environment(false);  // the |$hostname| does not have configuration

        return new Environment(true, $configuration, $settings);
    }

    // Initializes a new environment for |$settings|, only intended for use by tests. The |$valid|
    // boolean indicates whether the created environment should be valid.
    public static function createForTests(
            bool $valid, Configuration $configuration, array $settings): Environment {
        return new Environment($valid, $configuration, $settings);
    }

    private bool $valid;

    private string $contactName;
    private string $contactTarget;
    private array $events;
    private string $title;

    // Constructor for the Environment class. The |$valid| boolean must be set, and, when set to
    // true, the |$settings| array must be given with all intended options.
    private function __construct(
            bool $valid, Configuration $configuration = null, array $settings = []) {
        $this->valid = $valid;
        if (!$valid)
            return;

        $this->contactName = $settings['contactName'];
        $this->contactTarget = $settings['contactTarget'];
        $this->events = [];
        $this->title = $settings['title'];

        if (array_key_exists('events', $settings)) {
            foreach ($settings['events'] as $eventIdentifier => $eventOverrides) {
                $eventSettings = $configuration->get('events/' . $eventIdentifier);
                $eventSettings = array_merge($eventSettings, $eventOverrides);

                $event = new Event($eventIdentifier, $eventSettings);
                if (!$event->isValid())
                    continue;

                $this->events[] = $event;
            }
        }
    }

    // Returns whether this Environment instance represents a valid environment.
    public function isValid(): bool {
        return $this->valid;
    }

    // Returns the name of the person who can be contacted for questions.
    public function getContactName(): string {
        return $this->contactName;
    }

    // Returns the link target of the person who can be contacted for questions.
    public function getContactTarget(): string {
        return $this->contactTarget;
    }

    // Returns an array with all the Event instances known to this environment.
    public function getEvents(): array {
        return $this->events;
    }

    // Returns the name of the Volunteer Portal instance, e.g. Volunteer Portal.
    public function getTitle(): string {
        return $this->title;
    }
}
