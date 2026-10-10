<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Support\Facades\DB;

class RouteService
{
    /**
     * Get routes for company.
     */
    public function getRoutes(?int $companyId = null)
    {
        $query = Route::with(['branch', 'drivers.user'])
            ->select('*', DB::raw('ST_AsText(polygon) as wkt_polygon'));

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $routes = $query->get();

        return $routes->map(function ($route) {
            $route->polygon_coordinates = $this->parseWktToCoordinates($route->wkt_polygon);
            unset($route->wkt_polygon);
            return $route;
        });
    }

    /**
     * Get a single route by ID.
     */
    public function getRoute(int $id, ?int $companyId = null)
    {
        $query = Route::with(['branch', 'drivers.user'])
            ->select('*', DB::raw('ST_AsText(polygon) as wkt_polygon'))
            ->where('id', $id);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $route = $query->first();

        if ($route) {
            $route->polygon_coordinates = $this->parseWktToCoordinates($route->wkt_polygon);
            unset($route->wkt_polygon);
        }

        return $route;
    }

    /**
     * Create a route with a geographic polygon.
     */
    public function createRoute(array $data, ?int $companyId = null): Route
    {
        return DB::transaction(function () use ($data, $companyId) {
            $route = Route::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
            ]);

            $coordsToReturn = [];
            if (!empty($data['polygon_coordinates'])) {
                $coords = $data['polygon_coordinates'];
                
                // Asegurar que el polígono se cierra (el primer y último punto deben ser iguales)
                $firstPoint = $coords[0];
                $lastPoint = end($coords);
                if ($firstPoint[0] != $lastPoint[0] || $firstPoint[1] != $lastPoint[1]) {
                    $coords[] = $firstPoint;
                }

                // Construir string POLYGON((lon lat, lon lat...))
                // array structure is [latitude, longitude]
                $pointsString = array_map(function($point) {
                    return "{$point[1]} {$point[0]}"; // lon lat
                }, $coords);
                
                $wkt = "POLYGON((" . implode(", ", $pointsString) . "))";

                // Usar raw DB statement para guardar la geometría espacial nativa
                $route->update([
                    'polygon' => DB::raw("ST_GeomFromText('{$wkt}')")
                ]);

                $coordsToReturn = $coords;
            }

            $route = $route->refresh()->load(['branch', 'drivers.user']);
            $route->polygon_coordinates = $coordsToReturn;
            
            return $route;
        });
    }

    private function parseWktToCoordinates(?string $wkt): array
    {
        if (!$wkt) return [];
        
        $wkt = str_replace(['POLYGON((', 'POLYGON ((', '))', 'POLYGON(', ')'], '', $wkt);
        $points = explode(',', $wkt);
        $coordinates = [];
        
        foreach ($points as $point) {
            $coords = explode(' ', trim($point));
            if (count($coords) >= 2) {
                // Return back as [latitude, longitude] for the frontend map
                $coordinates[] = [(float) $coords[1], (float) $coords[0]];
            }
        }
        
        return $coordinates;
    }

    /**
     * Update an existing route.
     */
    public function updateRoute(int $id, array $data, ?int $companyId = null): ?Route
    {
        $route = Route::where('id', $id);
        if ($companyId !== null) {
            $route->where('company_id', $companyId);
        }
        $route = $route->firstOrFail();

        return DB::transaction(function () use ($route, $data) {
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['active'])) $updateData['active'] = $data['active'];
            if (isset($data['branch_id'])) $updateData['branch_id'] = $data['branch_id'];

            if (!empty($updateData)) {
                $route->update($updateData);
            }

            $coordsToReturn = [];
            if (!empty($data['polygon_coordinates'])) {
                $coords = $data['polygon_coordinates'];
                
                $firstPoint = $coords[0];
                $lastPoint = end($coords);
                if ($firstPoint[0] != $lastPoint[0] || $firstPoint[1] != $lastPoint[1]) {
                    $coords[] = $firstPoint;
                }

                $pointsString = array_map(function($point) {
                    return "{$point[1]} {$point[0]}"; 
                }, $coords);
                
                $wkt = "POLYGON((" . implode(", ", $pointsString) . "))";

                $route->update([
                    'polygon' => DB::raw("ST_GeomFromText('{$wkt}')")
                ]);

                $coordsToReturn = $coords;
            } else {
                // If polygon not updated, fetch existing one
                $existing = DB::table('routes')
                    ->selectRaw('ST_AsText(polygon) as wkt_polygon')
                    ->where('id', $route->id)
                    ->first();
                if ($existing) {
                    $coordsToReturn = $this->parseWktToCoordinates($existing->wkt_polygon);
                }
            }

            $route = $route->refresh()->load(['branch', 'drivers.user']);
            $route->polygon_coordinates = $coordsToReturn;
            
            return $route;
        });
    }

    /**
     * Delete a route.
     */
    public function deleteRoute(int $id, ?int $companyId = null): bool
    {
        $route = Route::where('id', $id);
        if ($companyId !== null) {
            $route->where('company_id', $companyId);
        }
        $route = $route->firstOrFail();

        return $route->delete();
    }
}
