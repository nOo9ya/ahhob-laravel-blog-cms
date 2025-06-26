<?php

namespace App\Contracts\Blog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 저장소 패턴 기본 인터페이스
 * 
 * 모든 Repository 클래스가 구현해야 하는 기본 CRUD 메서드를 정의합니다.
 * 이를 통해 일관된 데이터 접근 계층을 구축하고 테스트 가능한 구조를 만듭니다.
 */
interface RepositoryInterface
{
    /**
     * ID로 모델 조회
     * 
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model;

    /**
     * ID로 모델 조회 (없으면 예외 발생)
     * 
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * 모든 모델 조회
     * 
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * 페이지네이션된 모델 목록 조회
     * 
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * 새 모델 생성
     * 
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * 모델 업데이트
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * 모델 삭제
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * 조건으로 모델 조회
     * 
     * @param array $criteria
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    /**
     * 조건으로 첫 번째 모델 조회
     * 
     * @param array $criteria
     * @param array $columns
     * @return Model|null
     */
    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model;

    /**
     * 여러 관계 즉시 로딩
     * 
     * @param array|string $relations
     * @return self
     */
    public function with($relations): self;

    /**
     * 조건 추가
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where(string $column, $operator = null, $value = null): self;

    /**
     * 정렬 추가
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * 결과 제한
     * 
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self;

    /**
     * 쿼리 리셋
     * 
     * @return self
     */
    public function resetQuery(): self;
}