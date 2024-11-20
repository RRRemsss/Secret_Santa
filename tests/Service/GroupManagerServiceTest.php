<?php

namespace App\Tests\Service;

use App\Repository\GroupRepository;
use App\Repository\ParticipantRepository;
use App\Service\GroupManagerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class GroupManagerServiceTest extends TestCase
{
    private $entityManager;
    private $participantRepository;
    private $groupRepository;
    private $service;

    protected function setUp(): void
    {
        // Création des mocks pour les dépendances
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->participantRepository = $this->createMock(ParticipantRepository::class);
        $this->groupRepository = $this->createMock(GroupRepository::class);

        // Instanciation du service avec les mocks
        $this->service = new GroupManagerService(
            $this->entityManager,
            $this->participantRepository,
            $this->groupRepository
        );
    }
    
    public function testDrawParticipantsWithValidData()
    {
        $participants = [
            ['id' => 1, 'name' => 'Sergio', 'email' => 'sergio@test.fr', 'exclusion' => [2]],
            ['id' => 2, 'name' => 'Rémy', 'email' => 'remy@test.fr', 'exclusion' => [1]],
            ['id' => 3, 'name' => 'Alex', 'email' => 'alex@test.fr', 'exclusion' => [2, 4]],
            ['id' => 4, 'name' => 'Valentina', 'email' => 'valentina@test.fr', 'exclusion' => []],
        ];

        // Utilisation correcte de $this->service
        $result = $this->service->drawParticipants($participants);

        // Vérifiez que chaque participant a un destinataire différent
        $this->assertCount(count($participants), $result);

        // Vérifiez que chaque destinataire respecte les exclusions
        foreach ($participants as $participant) {
            $receiverName = $result[$participant['name']];
            $receiver = array_values(array_filter($participants, fn($p) => $p['name'] === $receiverName))[0] ?? null;

            $this->assertNotNull($receiver);
            $this->assertNotContains($receiver['id'], $participant['exclusion']);
            $this->assertNotEquals($participant['name'], $receiver['name']);
        }
    }

    public function testDrawParticipantsThrowsExceptionWhenImpossible()
    {
        $this->expectException(\Exception::class);

        // Cas impossible : tous les participants s'excluent mutuellement
        $participants = [
            ['id' => 1, 'name' => 'Sergio', 'email' => 'sergio@test.fr', 'exclusion' => [2, 3, 4]],
            ['id' => 2, 'name' => 'Rémy', 'email' => 'remy@test.fr', 'exclusion' => [1, 3, 4]],
            ['id' => 3, 'name' => 'Alex', 'email' => 'alex@test.fr', 'exclusion' => [1, 2, 4]],
            ['id' => 4, 'name' => 'Valentina', 'email' => 'valentina@test.fr', 'exclusion' => [1, 2, 3]],
        ];

        $this->service->drawParticipants($participants);
    }
}
