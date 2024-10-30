<?php

namespace App\Entity;

use App\Repository\MonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MonthRepository::class)]
class Month
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["advice:read", "month:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["advice:read", "month:read"])]
    private ?string $name = null;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\ManyToMany(targetEntity: Advice::class, mappedBy: 'months')]
    #[Groups(["month:read"])]
    private Collection $advice;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $month_number = null;

    public function __construct()
    {
        $this->advice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advice;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advice->contains($advice)) {
            $this->advice->add($advice);
            $advice->addMonth($this);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        if ($this->advice->removeElement($advice)) {
            $advice->removeMonth($this);
        }

        return $this;
    }

    public function getMonthNumber(): ?int
    {
        return $this->month_number;
    }

    public function setMonthNumber(int $month_number): static
    {
        $this->month_number = $month_number;

        return $this;
    }
}
