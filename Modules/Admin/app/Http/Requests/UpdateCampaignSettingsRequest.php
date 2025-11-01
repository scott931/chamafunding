<?php

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Moderator']) ?? false;
    }

    public function rules(): array
    {
        return [
            // Campaign Requirements
            'campaign_requirements.min_funding_goal' => ['nullable', 'numeric', 'min:0'],
            'campaign_requirements.max_funding_goal' => ['nullable', 'numeric', 'min:0'],
            'campaign_requirements.min_duration_days' => ['nullable', 'integer', 'min:1'],
            'campaign_requirements.max_duration_days' => ['nullable', 'integer', 'min:1'],
            'campaign_requirements.required_video' => ['nullable', 'boolean'],
            'campaign_requirements.required_image_gallery' => ['nullable', 'boolean'],
            'campaign_requirements.required_story_text' => ['nullable', 'boolean'],

            // Approval Workflow
            'approval_workflow.require_approval' => ['nullable', 'boolean'],

            // Content Restrictions
            'content_restrictions.prohibited_categories' => ['nullable', 'array'],
            'content_restrictions.banned_keywords' => ['nullable', 'array'],
            'content_restrictions.manual_review_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
