<?php

namespace App\Enums;

enum AppraisalStatusEnum: string
{
    case DraftObjectives = 'draft_objectives';
    case ObjectivesAgreed = 'objectives_agreed';
    case MidYearInProgress = 'midyear_in_progress';
    case MidYearCompleted = 'midyear_completed';
    case SelfAppraisal = 'self_appraisal';
    case SupervisorReview = 'supervisor_review';
    case ReviewerReview = 'reviewer_review';
    case AwaitingAcknowledgement = 'awaiting_acknowledgement';
    case Completed = 'completed';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::DraftObjectives => 'Draft Objectives',
            self::ObjectivesAgreed => 'Objectives Agreed',
            self::MidYearInProgress => 'Mid-Year In Progress',
            self::MidYearCompleted => 'Mid-Year Completed',
            self::SelfAppraisal => 'Self Appraisal',
            self::SupervisorReview => 'Supervisor Review',
            self::ReviewerReview => 'Reviewer Review',
            self::AwaitingAcknowledgement => 'Awaiting Acknowledgement',
            self::Completed => 'Completed',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'text-green-500',
            self::Returned => 'text-red-500',
            self::AwaitingAcknowledgement => 'text-amber-500',
            default => 'dark:text-gray-100 text-gray-700',
        };
    }
}
