<?php


function canAddPrerequisite(int $movieId, int $prereqId, array $graph): bool
{
  
    if ($movieId === $prereqId) {
        return false;
    }

    $visited = [];
    $stack = [$prereqId];

    while ($stack) {
        $node = array_pop($stack);

        if ($node === $movieId) {
            return false;
        }
        if (isset($visited[$node])) {
            continue;
        }
        $visited[$node] = true;

        foreach ($graph[$node] ?? [] as $p) {
            $stack[] = $p;
        }
    }

    return true;
}



function rewatchOrder(int $targetId, array $graph): array
{
    $visited = [];
    $order = [];
    $stack = [[$targetId, false]];

    while ($stack) {
        [$id, $expanded] = array_pop($stack);

        if ($expanded) {
            if ($id !== $targetId) {
                $order[] = $id;
            }
            continue;
        }
        
        if (isset($visited[$id])) {
            continue;
        }
        $visited[$id] = true;

        $stack[] = [$id, true];  
        foreach ($graph[$id] ?? [] as $p) {
            $stack[] = [$p, false];
        }
    }


    return $order;
}


function minBundles(array $sizes, int $groupSize): ?array
{
    $INF = PHP_INT_MAX;
    $dp = array_fill(0, $groupSize + 1, $INF);   
    $choice = array_fill(0, $groupSize + 1, 0);  
    $dp[0] = 0;                                  

    foreach ($sizes as $size) {                
        for ($j = $size; $j <= $groupSize; $j++) {   
            if ($dp[$j - $size] !== $INF && $dp[$j - $size] + 1 < $dp[$j]) {
                $dp[$j] = $dp[$j - $size] + 1;
                $choice[$j] = $size;
            }
        }
    }
    

    if ($dp[$groupSize] === $INF) {
        return null;                             
    }

    
    $counts = [];
    $x = $groupSize;
    while ($x > 0) {
        $size = $choice[$x];
        $counts[$size] = ($counts[$size] ?? 0) + 1;
        $x -= $size;
    }
    krsort($counts);                            

    $bundles = [];
    foreach ($counts as $size => $count) {
        $bundles[] = ['size' => $size, 'count' => $count];
    }
    return ['totalBundles' => $dp[$groupSize], 'bundles' => $bundles];
}