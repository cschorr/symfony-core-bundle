<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ProjectController extends AbstractController
{
    #[Route('/project/{id}/transition/{transition}', name: 'project_transition')]
    public function transitionProject(
        Project $project,
        string $transition,
        WorkflowInterface $projectStatusStateMachine,
        EntityManagerInterface $entityManager,
    ): Response {
        // Prüfen, ob die Transition möglich ist
        if (!$projectStatusStateMachine->can($project, $transition)) {
            $this->addFlash('error', 'Transition nicht möglich');

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Transition ausführen
        $projectStatusStateMachine->apply($project, $transition);

        // Projekt speichern
        $entityManager->flush();

        $this->addFlash('success', 'Projektstatus erfolgreich geändert');

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }
}
