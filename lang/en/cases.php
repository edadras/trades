<?php

return [
    'status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'ai_processing' => 'AI processing',
        'human_review' => 'Human review',
        'ready' => 'Ready',
        'matching' => 'Matching',
        'expert_proposed' => 'Expert proposed',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'waiting' => 'Waiting',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],
    'errors' => [
        'invalid_transition' => 'Cannot move the case from “:from” to “:to”.',
        'outcome_required' => 'A case cannot be closed without recording an outcome.',
        'empty_problem' => 'Please describe your problem in text or by voice.',
        'onboarding_required' => 'Please complete your business registration first.',
    ],
    'default_info_request' => 'Please send additional information and documents for this case.',
    'created' => 'Case created.',
    'submitted' => 'Case submitted and being analysed.',
    'saved' => 'Changes saved.',
    'closed' => 'Case closed.',
];
