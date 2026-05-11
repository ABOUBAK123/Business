<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Notification générique.
     */
    public static function notify(
        string $recipientId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $workflowId = null,
        ?string $executionId = null
    ): void {
        if (trim($recipientId) === '') {
            return;
        }

        Notification::create([
            'id' => (string) Str::uuid(),
            'recipient_id' => $recipientId,
            'type' => self::sanitizeType($type),
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'is_read' => false,
        ]);
    }

    /**
     * Notifie un utilisateur qu'un template lui a ete partage.
     */
    public static function templateShared(object $template, string $recipientId, string $sharedByName): void
    {
        self::notify(
            recipientId: $recipientId,
            type: 'info',
            title: 'Template partage',
            message: sprintf('Le template "%s" vous a ete partage par %s.', $template->name ?? 'Sans nom', $sharedByName),
            actionUrl: route('shared-templates.index')
        );
    }

    /**
     * Notifie le destinataire d'un message chat direct.
     */
    public static function chatMessageReceived(object $message, string $senderName): void
    {
        $recipientId = (string) ($message->recipient_id ?? '');
        $senderId = (string) ($message->sender_id ?? '');
        if ($recipientId === '' || $recipientId === $senderId) {
            return;
        }

        self::notify(
            recipientId: $recipientId,
            type: 'info',
            title: 'Nouveau message',
            message: sprintf('%s vous a envoye un message.', $senderName),
            actionUrl: route('chat.index')
        );
    }

    /**
     * Notifie les assignees des etapes lors de la creation d'un workflow.
     */
    public static function workflowStepsAssigned(object $workflow, iterable $steps, string $actorName): void
    {
        $notified = [];
        foreach ($steps as $step) {
            $assigneeId = (string) ($step->assignee_id ?? '');
            if ($assigneeId === '' || isset($notified[$assigneeId])) {
                continue;
            }

            $notified[$assigneeId] = true;

            self::notify(
                recipientId: $assigneeId,
                type: 'workflow',
                title: 'Etape de workflow assignee',
                message: sprintf('Vous etes assigne a une etape du workflow "%s" par %s.', $workflow->name ?? 'Sans nom', $actorName),
                actionUrl: route('workflows.index') . '#en-cours',
                workflowId: (string) ($workflow->id ?? null)
            );
        }
    }

    /**
     * Notifie l'assigne de la premiere etape a l'execution.
     */
    public static function workflowExecutionStarted(object $workflow, object $firstStep, string $actorName): void
    {
        $assigneeId = (string) ($firstStep->assignee_id ?? '');
        if ($assigneeId === '') {
            return;
        }

        self::notify(
            recipientId: $assigneeId,
            type: 'workflow',
            title: 'Workflow demarre',
            message: sprintf('Le workflow "%s" a ete lance par %s.', $workflow->name ?? 'Sans nom', $actorName),
            actionUrl: route('workflows.index') . '#en-cours',
            workflowId: (string) ($workflow->id ?? null)
        );
    }

    /**
     * Notifie l'assigne de la prochaine etape lors d'une avancee.
     */
    public static function workflowStepAdvanced(object $workflow, int $nextStepOrder, string $actorName): void
    {
        $nextStep = null;
        if (method_exists($workflow, 'steps')) {
            $nextStep = $workflow->steps()->where('order', $nextStepOrder)->first();
        }

        $assigneeId = (string) ($nextStep->assignee_id ?? '');
        if ($assigneeId === '') {
            return;
        }

        self::notify(
            recipientId: $assigneeId,
            type: 'workflow',
            title: 'Etape suivante du workflow',
            message: sprintf('Le workflow "%s" a avance a votre etape par %s.', $workflow->name ?? 'Sans nom', $actorName),
            actionUrl: route('workflows.index') . '#en-cours',
            workflowId: (string) ($workflow->id ?? null)
        );
    }

    /**
     * Limite les types aux valeurs enum de la table notifications.
     */
    private static function sanitizeType(string $type): string
    {
        $allowed = ['info', 'validation', 'signature', 'workflow', 'system'];
        $normalized = strtolower(trim($type));

        return in_array($normalized, $allowed, true) ? $normalized : 'info';
    }
}
