<?php

return [
    /*
    | House-level liege/vassal politics (Phase II Agent 4).
    | No lore branches. Numeric policy below is a deterministic placeholder (U21).
    */

    'standing_min' => -100,
    'standing_max' => 100,
    'standing_start' => 0,

    /*
    | Allegiance is derived from standing (plus active dispute markers).
    | Start standing 0 => loyal. Not a second independent meter.
    */
    'allegiance' => [
        'loyal_min' => 0,
        'strained_min' => -25,
        'disputed_min' => -50,
    ],

    'obligation_standing' => [
        'fulfilled' => 5,
        'partial' => 0,
        'refused' => -15,
        'defaulted' => -25,
    ],

    'sanction_standing' => [
        'reprimand' => -5,
        'standing_penalty' => -10,
        'monetary_penalty' => -5,
        'obligation_escalation' => -5,
        'dispute_marker' => -10,
    ],

    /*
    | Petition policy thresholds (placeholder NPC resolver, not AI).
    | title_request is never auto-granted (title granting is not this agent's domain).
    */
    'petition' => [
        'grant_min_standing' => 25,
        'deny_max_standing' => -25,
    ],
];
