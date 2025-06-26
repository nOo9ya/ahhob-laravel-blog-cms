<?php

namespace App\Repositories\Blog;

use App\Contracts\Blog\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 기본 저장소 클래스
 * 
 * RepositoryInterface의 기본 구현을 제공합니다.
 * 모든 구체적인 Repository 클래스는 이 클래스를 상속받아 사용할 수 있습니다.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * Eloquent 모델 인스턴스
     * 
     * @var Model
     */
    protected Model $model;

    /**
     * 현재 쿼리 빌더
     * 
     * @var Builder
     */
    protected Builder $query;

    /**
     * 생성자
     * 
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * ID로 모델 조회
     * 
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * ID로 모델 조회 (없으면 예외 발생)
     * 
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * 모든 모델 조회
     * 
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * 페이지네이션된 모델 목록 조회
     * 
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetQuery();
        
        return $result;
    }

    /**
     * 새 모델 생성
     * 
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * 모델 업데이트
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->findOrFail($id)->update($data);
    }

    /**
     * 모델 삭제
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    /**
     * 조건으로 모델 조회
     * 
     * @param array $criteria
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $this->applyCriteria($criteria);
        $result = $this->query->get($columns);
        $this->resetQuery();
        
        return $result;
    }

    /**
     * 조건으로 첫 번째 모델 조회
     * 
     * @param array $criteria
     * @param array $columns
     * @return Model|null
     */
    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model
    {
        $this->applyCriteria($criteria);
        $result = $this->query->first($columns);
        $this->resetQuery();
        
        return $result;
    }

    /**
     * 여러 관계 즉시 로딩
     * 
     * @param array|string $relations
     * @return self
     */
    public function with($relations): self
    {
        $this->query = $this->query->with($relations);
        return $this;
    }

    /**
     * 조건 추가
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query = $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * 정렬 추가
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query = $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * 결과 제한
     * 
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->query = $this->query->limit($limit);
        return $this;
    }

    /**
     * 쿼리 리셋
     * 
     * @return self
     */
    public function resetQuery(): self
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * 결과 가져오기
     * 
     * @param array $columns
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection
    {
        $result = $this->query->get($columns);
        $this->resetQuery();
        
        return $result;
    }

    /**
     * 첫 번째 결과 가져오기
     * 
     * @param array $columns
     * @return Model|null
     */
    public function first(array $columns = ['*']): ?Model
    {
        $result = $this->query->first($columns);
        $this->resetQuery();
        
        return $result;
    }

    /**
     * 조건들을 쿼리에 적용
     * 
     * @param array $criteria
     * @return void
     */
    protected function applyCriteria(array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $this->query = $this->query->whereIn($field, $value);
            } else {
                $this->query = $this->query->where($field, $value);
            }
        }
    }

    /**
     * 새 모델 인스턴스 반환
     * 
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * 현재 쿼리 빌더 반환
     * 
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}