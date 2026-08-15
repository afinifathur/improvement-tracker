<?php

namespace App\Enums;

enum WorkType: string
{
    case Routine = 'routine';
    case ProblemSolving = 'problem_solving';
    case Improvement = 'improvement';
    case StrategicImprovement = 'strategic_improvement';
}
