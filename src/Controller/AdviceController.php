<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdviceController extends AbstractController
{
    #[Route('/api/conseil', name: 'current_month_advices', methods: ['GET'])]
    public function currentMonthAdvices(MonthRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $advices = $serializer->serialize($this->getAdviceByMonth(date('n'), $repo), 'json', ['groups' => 'advice:read']
        );
        return new JsonResponse($advices, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseil/{month}', name: 'selected_month_advices', methods: ['GET'])]
    public function selectedMonthAdvices(
        int $month,
        MonthRepository $repo,
        SerializerInterface $serializer
    ): JsonResponse {
        if ($month < 1 || $month > 12) {
            return new JsonResponse([
                'message' => 'Month must be between 1 and 12'
            ], Response::HTTP_BAD_REQUEST);
        }
        $advices = $serializer->serialize($this->getAdviceByMonth($month, $repo), 'json', ['groups' => 'advice:read']);
        return new JsonResponse($advices, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/conseil', name: 'add_advice', methods: ['POST'])]
    public function addAdvice(
        Request $request,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        MonthRepository $repo
    ): JsonResponse {
        $advice = $this->createAdviceFromRequest($request, $serializer, $repo);
        if ($advice instanceof JsonResponse) {
            return $advice;
        }
        $isValid = $this->isValidAdvice($advice, $validator);
        if (!($isValid instanceof JsonResponse)) {
            try {
                $manager->persist($advice);
                $manager->flush();
                return new JsonResponse([
                    'message' => 'Advice added'
                ], Response::HTTP_CREATED);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => 'An error occurred while saving the advice : ' . $e
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return $isValid;
        }
    }

    #[Route('/api/admin/conseil/{id}', name: 'update_advice', methods: ['PUT'])]
    public function updateAdvice(
        Advice $advice,
        Request $request,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        MonthRepository $repo
    ): JsonResponse {
        $newAdvice = $this->createAdviceFromRequest($request, $serializer, $repo);
        if ($newAdvice instanceof JsonResponse) {
            return $newAdvice;
        }
        $isValid = $this->isValidAdvice($newAdvice, $validator);
        if (!($isValid instanceof JsonResponse)) {
            try {
                if ($advice->getContent() === $newAdvice->getContent() &&
                    $advice->getMonths() === $newAdvice->getMonths()) {
                    return new JsonResponse([
                        'message' => 'No changes detected'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $advice->setContent($newAdvice->getContent());
                foreach ($advice->getMonths() as $originalMonth) {
                    if (!$newAdvice->getMonths()->contains($originalMonth)) {
                        $advice->removeMonth($originalMonth);
                    }
                }
                foreach ($newAdvice->getMonths() as $newMonth) {
                    if (!$advice->getMonths()->contains($newMonth)) {
                        $advice->addMonth($newMonth);
                    }
                }
                $manager->flush();
                return new JsonResponse([
                    'message' => 'Advice updated'
                ], Response::HTTP_OK);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => 'An error occurred while updating the advice : ' . $e[0]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        else {
            return $isValid;
        }
    }

    #[Route('/api/admin/conseil/{id}', name: 'delete_advice', methods: ['DELETE'])]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $manager): JsonResponse
    {
        try {
            $manager->remove($advice);
            $manager->flush();
            return new JsonResponse([
                'message' => 'Advice deleted'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'An error occurred while deleting the advice'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createAdviceFromRequest(
        Request $request,
        SerializerInterface $serializer,
        MonthRepository $repo
    ): Advice|JsonResponse {
        $data = $request->getContent();
        try {
            $data = $serializer->decode($data, JsonEncoder::FORMAT);
            $advice = new Advice();
            $advice->setContent($data['content']);
            foreach ($data['months'] as $month) {
                $monthToAdd = $repo->findOneBy(['month_number' => $month]);
                if ($monthToAdd == null) {
                    return new JsonResponse([
                        'message' => 'Month ' . $month . ' does not exist'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $advice->addMonth($monthToAdd);
            }
            return $advice;
        } catch (NotEncodableValueException $e) {
            return new JsonResponse([
                'message' => 'Le format des données envoyées est invalide'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function isValidAdvice(Advice $advice, ValidatorInterface $validator): true|JsonResponse
    {
        $errors = $validator->validate($advice);
        if (count($errors) > 0) {
            return new JsonResponse([
                'message' => 'Wrong datas format : ' . $errors[0]->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } else {
            return true;
        }
    }

    private function getAdviceByMonth(int $month, MonthRepository $repo): PersistentCollection|array
    {
        $month = $repo->findOneBy(['month_number' => $month]);


        return $month ? $month->getAdvice() : [];
    }
}
