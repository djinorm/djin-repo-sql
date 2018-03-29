<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 20.02.2018 16:52
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use DjinORM\Djin\Helpers\DjinHelper;
use DjinORM\Djin\Model\ModelInterface;
use InvalidArgumentException;

abstract class SubmodelRepository extends MapperSqlRepository
{

    protected $parentPreload;

    /**
     * @param ModelInterface[] $models
     * @param ModelInterface $parent
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function saveModels(array $models, ModelInterface $parent)
    {
        $parentId = $parent->getId()->toScalar();
        $loadedModels = $this->parentPreload[$parentId] ?? [];

        $currentModels = [];

        foreach ($models as $model) {
            $currentModels[spl_object_hash($model)] = $model;
        }

        $deletedModels = array_diff_key($loadedModels, $currentModels);
        foreach ($deletedModels as $model) {
            $this->delete($model, $parent);
        }

        $newModels = array_diff_key($currentModels, $loadedModels);
        foreach ($newModels as $model) {
            $this->insert($model, $parent);
        }

        $updatedModels = array_intersect_key($currentModels, $loadedModels);
        foreach ($updatedModels as $model) {
            $this->update($model, $parent);
        }
    }

    /**
     * @param ModelInterface $model
     * @param ModelInterface|null $parent
     * @return mixed|void
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function save(ModelInterface $model, ModelInterface $parent = null)
    {
        $this->guardNullParent($parent);
        if (isset($this->models[$model->getId()->toScalar()])) {
            $this->update($model, $parent);
        } else {
            $this->insert($model, $parent);
        }
    }

    /**
     * @param ModelInterface $model
     * @param ModelInterface|null $parent
     * @return mixed|void
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function insert(ModelInterface $model, ModelInterface $parent = null)
    {
        $this->guardNullParent($parent);

        $this->setPermanentId($model);
        $data = $this->extract($model, $parent);
        $insert = $this->builder->newInsert()->into($this->getTableName());
        $insert->cols($data);
        $this->insertStatement($insert);

        $this->preload($parent, $model);
    }

    /**
     * @param ModelInterface $model
     * @param ModelInterface|null $parent
     * @return mixed|void
     */
    public function update(ModelInterface $model, ModelInterface $parent = null)
    {
        $this->guardNullParent($parent);

        $data = $this->extract($model, $parent);
        $update = $this->builder->newUpdate()->table($this->getTableName());
        $update->cols($data);

        $update->where("{$this->getIdName()} = :id")
            ->bindValue('id', $model->getId()->toScalar());

        $update->where("{$this->getParentIdColumnName()} = :parentId")
            ->bindValue('parentId', $parent->getId()->toScalar());

        $this->updateStatement($update);
    }

    /**
     * @param ModelInterface $model
     * @param ModelInterface|null $parent
     * @return mixed|void
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function delete(ModelInterface $model, ModelInterface $parent = null)
    {
        $this->guardNullParent($parent);

        $delete = $this->builder->newDelete()->from($this->getTableName());

        $delete->where("{$this->getIdName()} = :id")
            ->bindValue('id', $model->getId()->toScalar());

        $delete->where("{$this->getParentIdColumnName()} = :parentId")
            ->bindValue('parentId', $parent->getId()->toScalar());

        $this->deleteStatement($delete);
        unset($this->models[$model->getId()->toScalar()]);

        $parentId = DjinHelper::getScalarId($parent);
        unset($this->parentPreload[$parentId][spl_object_hash($model)]);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function preloadByParentIds(array $ids): array
    {
        $result = [];
        $idsChunk = array_chunk($ids, 1000);
        foreach ($idsChunk as $ids) {
            $select = $this->select()->where($this->getParentIdColumnName() . " IN (:ids)")->bindValue('ids', $ids);
            $result = array_merge($result, $this->fetchAndPopulateMany($select));
        }
        return array_values($result);
    }

    /**
     * @param $parentOrId
     * @return array
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function loadForParent($parentOrId): array
    {
        $parentId = DjinHelper::getScalarId($parentOrId);
        if (isset($this->parentPreload[$parentId])) {
            return array_values($this->parentPreload[$parentId]);
        }
        return [];
    }

    /**
     * @return mixed|void
     */
    public function freeUpMemory()
    {
        parent::freeUpMemory();
        $this->parentPreload = [];
    }

    /**
     * @param ModelInterface $object
     * @param ModelInterface|null $parent
     * @return array
     */
    protected function extract(ModelInterface $object, ModelInterface $parent = null): array
    {
        $this->guardNullParent($parent);
        $data = parent::extract($object);
        $data[$this->getParentIdColumnName()] = $parent->getId()->toScalar();
        return $data;
    }

    /**
     * @param $data
     * @return ModelInterface|null
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    protected function populateOne($data): ?ModelInterface
    {
        $model = parent::populateOne($data);
        if ($model) {
            $parentId = $data[$this->getParentIdColumnName()];
            $this->preload($parentId, $model);
        }
        return $model;
    }

    abstract protected function getParentIdColumnName(): string;

    /**
     * @param $parentOrId
     * @param ModelInterface $model
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    protected function preload($parentOrId, ModelInterface $model)
    {
        $parentId = DjinHelper::getScalarId($parentOrId);
        $this->parentPreload[$parentId][spl_object_hash($model)] = $model;
    }

    /**
     * @param ModelInterface $parent
     */
    private function guardNullParent(ModelInterface $parent)
    {
        if ($parent === null) {
            throw new InvalidArgumentException('Model can not be saved without parent');
        }
    }

}