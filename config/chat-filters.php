<?php

/**
 * Pre-populated filter word lists organized by safety category.
 *
 * Categories can be toggled by admins in Chat Settings.
 * When NSFW mode is enabled, profanity/sexual filters are relaxed.
 *
 * Sources: Compiled from commonly-used open-source profanity lists
 * including "List of Dirty, Naughty, Obscene, and Otherwise Bad Words"
 * (LDNOOBW), Google's "What Do You Love" banned words list, and
 * community-maintained moderation lists.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Child Safety
    |--------------------------------------------------------------------------
    | Words/phrases related to child exploitation, grooming, and predatory
    | behavior. These should ALWAYS be filtered regardless of NSFW mode.
    */
    'child_safety' => [
        'label' => 'Child Safety',
        'description' => 'Blocks grooming, exploitation, and predatory language targeting minors',
        'always_active_in_nsfw' => true,
        'words' => [
            'cp',
            'child porn',
            'child pornography',
            'kiddie porn',
            'pedo',
            'pedophile',
            'pedophilia',
            'paedophile',
            'paedophilia',
            'grooming',
            'lolicon',
            'shotacon',
            'jailbait',
            'underage',
            'minor sex',
            'child abuse',
            'child molest',
            'child exploit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Animal Safety
    |--------------------------------------------------------------------------
    | Words/phrases related to animal cruelty and abuse.
    */
    'animal_safety' => [
        'label' => 'Animal Safety',
        'description' => 'Blocks animal cruelty, abuse, and bestiality content',
        'always_active_in_nsfw' => true,
        'words' => [
            'animal abuse',
            'animal cruelty',
            'animal torture',
            'bestiality',
            'zoophilia',
            'zoophile',
            'dog fighting',
            'cockfighting',
            'animal crush',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Racial / Political Safety
    |--------------------------------------------------------------------------
    | Slurs, hate speech, and extremist terminology targeting race, ethnicity,
    | religion, gender identity, and sexual orientation.
    */
    'racial_political_safety' => [
        'label' => 'Racial / Political Safety',
        'description' => 'Blocks racial slurs, hate speech, and extremist terminology',
        'always_active_in_nsfw' => true,
        'words' => [
            // Racial slurs
            'nigger',
            'nigga',
            'negro',
            'coon',
            'darkie',
            'wetback',
            'beaner',
            'spic',
            'chink',
            'gook',
            'kike',
            'hymie',
            'raghead',
            'towelhead',
            'camel jockey',
            'sand nigger',
            'paki',
            'wog',
            'abo',
            'redskin',
            'injun',
            'squaw',
            'zipperhead',
            'slope',
            'jap',
            // Anti-LGBTQ+ slurs
            'faggot',
            'fag',
            'dyke',
            'tranny',
            'shemale',
            'he-she',
            // Antisemitic
            'gas the jews',
            'holocaust denial',
            // Extremist
            'white power',
            'white supremacy',
            'heil hitler',
            'sieg heil',
            'ethnic cleansing',
            'race war',
            '14 words',
            '1488',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | General Profanity
    |--------------------------------------------------------------------------
    | Common swear words and vulgar language. These are RELAXED when
    | NSFW mode is enabled, allowing adults to speak freely.
    */
    'profanity' => [
        'label' => 'General Profanity',
        'description' => 'Common swear words and vulgar language (disabled in NSFW mode)',
        'always_active_in_nsfw' => false,
        'words' => [
            'fuck',
            'fucking',
            'fucked',
            'fucker',
            'motherfucker',
            'motherfucking',
            'shit',
            'shitty',
            'bullshit',
            'horseshit',
            'ass',
            'asshole',
            'arsehole',
            'bitch',
            'bitchy',
            'bastard',
            'damn',
            'dammit',
            'goddamn',
            'hell',
            'crap',
            'piss',
            'pissed',
            'dick',
            'dickhead',
            'cock',
            'cocksucker',
            'cunt',
            'twat',
            'wanker',
            'tosser',
            'bollocks',
            'bugger',
            'bloody',
            'whore',
            'slut',
            'skank',
            'tit',
            'tits',
            'boob',
            'boobs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sexual Content
    |--------------------------------------------------------------------------
    | Explicit sexual terms. Relaxed in NSFW mode.
    */
    'sexual_content' => [
        'label' => 'Sexual Content',
        'description' => 'Explicit sexual language and terminology (disabled in NSFW mode)',
        'always_active_in_nsfw' => false,
        'words' => [
            'blowjob',
            'handjob',
            'rimjob',
            'cumshot',
            'creampie',
            'gangbang',
            'orgy',
            'threesome',
            'anal sex',
            'oral sex',
            'bondage',
            'bdsm',
            'dildo',
            'vibrator',
            'masturbat',
            'ejaculat',
            'orgasm',
            'erection',
            'pornography',
            'porn',
            'hentai',
            'xxx',
            'nsfw',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Violence & Threats
    |--------------------------------------------------------------------------
    | Threats, extreme violence, and harmful instructions.
    */
    'violence_threats' => [
        'label' => 'Violence & Threats',
        'description' => 'Blocks threats of violence, gore, and harmful instructions',
        'always_active_in_nsfw' => true,
        'words' => [
            'kill yourself',
            'kys',
            'go die',
            'i will kill',
            'death threat',
            'bomb threat',
            'shoot up',
            'school shooting',
            'mass shooting',
            'how to make a bomb',
            'how to make poison',
            'swatting',
            'doxxing',
            'dox',
        ],
    ],
];
