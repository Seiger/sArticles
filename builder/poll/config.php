<?php return [
    'active' => (evo()->getConfig('sart_polls_on', 1) == 1 ? 1 : 0),
    'title' => 'Poll',
    'type' => 'poll',
    'id' => 'poll',
    'order' => 7,
    'script' => '',
];
