<?php

namespace App\Traits\Ahhob\Blog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * 쿼리 빌더 공통 기능 트레이트
 * 
 * 컨트롤러와 서비스에서 자주 사용되는 쿼리 빌딩 로직을 재사용 가능하게 만듭니다.
 * 필터링, 정렬, 검색 등의 공통 기능을 제공합니다.
 */
trait QueryBuilderTrait
{
    /**
     * 요청 파라미터를 기반으로 필터 적용
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $allowedFilters
     * @return Builder
     */
    protected function applyFilters(Builder $query, Request $request, array $allowedFilters = []): Builder
    {
        foreach ($allowedFilters as $filter => $column) {
            $value = $request->get($filter);
            
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }
        
        return $query;
    }

    /**
     * 검색 조건 적용
     * 
     * @param Builder $query
     * @param string|null $search
     * @param array $searchableColumns
     * @return Builder
     */
    protected function applySearch(Builder $query, ?string $search, array $searchableColumns = []): Builder
    {
        if (empty($search) || empty($searchableColumns)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * 정렬 조건 적용
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $allowedSorts
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return Builder
     */
    protected function applySorting(
        Builder $query, 
        Request $request, 
        array $allowedSorts = [], 
        string $defaultSort = 'id', 
        string $defaultDirection = 'desc'
    ): Builder {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);
        
        // 허용된 정렬 컬럼인지 확인
        if (!in_array($sort, $allowedSorts)) {
            $sort = $defaultSort;
        }
        
        // 정렬 방향 검증
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }
        
        return $query->orderBy($sort, $direction);
    }

    /**
     * 날짜 범위 필터 적용
     * 
     * @param Builder $query
     * @param Request $request
     * @param string $column
     * @param string $startParam
     * @param string $endParam
     * @return Builder
     */
    protected function applyDateRange(
        Builder $query, 
        Request $request, 
        string $column = 'created_at',
        string $startParam = 'start_date',
        string $endParam = 'end_date'
    ): Builder {
        $startDate = $request->get($startParam);
        $endDate = $request->get($endParam);
        
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * 상태 필터 적용
     * 
     * @param Builder $query
     * @param Request $request
     * @param string $column
     * @param string $param
     * @param array $allowedStatuses
     * @return Builder
     */
    protected function applyStatusFilter(
        Builder $query, 
        Request $request, 
        string $column = 'status',
        string $param = 'status',
        array $allowedStatuses = ['published', 'draft', 'archived']
    ): Builder {
        $status = $request->get($param);
        
        if ($status && in_array($status, $allowedStatuses)) {
            $query->where($column, $status);
        }
        
        return $query;
    }

    /**
     * 관계 필터 적용
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $relationFilters
     * @return Builder
     */
    protected function applyRelationFilters(Builder $query, Request $request, array $relationFilters = []): Builder
    {
        foreach ($relationFilters as $param => $config) {
            $value = $request->get($param);
            
            if ($value !== null && $value !== '') {
                $relation = $config['relation'];
                $column = $config['column'];
                
                $query->whereHas($relation, function ($q) use ($column, $value) {
                    if (is_array($value)) {
                        $q->whereIn($column, $value);
                    } else {
                        $q->where($column, $value);
                    }
                });
            }
        }
        
        return $query;
    }

    /**
     * 페이지네이션 설정 가져오기
     * 
     * @param Request $request
     * @param int $defaultPerPage
     * @param int $maxPerPage
     * @return int
     */
    protected function getPerPage(Request $request, int $defaultPerPage = 15, int $maxPerPage = 100): int
    {
        $perPage = (int) $request->get('per_page', $defaultPerPage);
        
        return min(max($perPage, 1), $maxPerPage);
    }

    /**
     * 쿼리 디버깅을 위한 SQL 출력
     * 
     * @param Builder $query
     * @return string
     */
    protected function getQuerySql(Builder $query): string
    {
        return $query->toSql();
    }

    /**
     * 복합 검색 조건 적용 (AND 조건)
     * 
     * @param Builder $query
     * @param array $conditions
     * @return Builder
     */
    protected function applyComplexSearch(Builder $query, array $conditions): Builder
    {
        foreach ($conditions as $condition) {
            $column = $condition['column'];
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'];
            
            switch ($operator) {
                case 'like':
                    $query->where($column, 'LIKE', "%{$value}%");
                    break;
                case 'in':
                    $query->whereIn($column, $value);
                    break;
                case 'between':
                    $query->whereBetween($column, $value);
                    break;
                case 'null':
                    $query->whereNull($column);
                    break;
                case 'not_null':
                    $query->whereNotNull($column);
                    break;
                default:
                    $query->where($column, $operator, $value);
            }
        }
        
        return $query;
    }

    /**
     * 즉시 로딩할 관계 설정
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $availableIncludes
     * @return Builder
     */
    protected function applyIncludes(Builder $query, Request $request, array $availableIncludes = []): Builder
    {
        $includes = $request->get('include', '');
        
        if (empty($includes)) {
            return $query;
        }
        
        $requestedIncludes = array_filter(explode(',', $includes));
        $validIncludes = array_intersect($requestedIncludes, $availableIncludes);
        
        if (!empty($validIncludes)) {
            $query->with($validIncludes);
        }
        
        return $query;
    }
}