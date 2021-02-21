<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineMongoDBAdminBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;
use Sonata\AdminBundle\Form\Type\Filter\DateTimeRangeType;
use Sonata\AdminBundle\Form\Type\Filter\DateTimeType;
use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\AdminBundle\Form\Type\Operator\DateOperatorType;
use Sonata\DoctrineMongoDBAdminBundle\Datagrid\ProxyQueryInterface;

abstract class AbstractDateFilter extends Filter
{
    /**
     * Flag indicating that filter will have range.
     *
     * @var bool
     */
    protected $range = false;

    /**
     * Flag indicating that filter will filter by datetime instead by date.
     *
     * @var bool
     */
    protected $time = false;

    public function getDefaultOptions(): array
    {
        return ['input_type' => 'datetime'];
    }

    public function getRenderSettings(): array
    {
        $name = DateType::class;

        if ($this->time && $this->range) {
            $name = DateTimeRangeType::class;
        } elseif ($this->time) {
            $name = DateTimeType::class;
        } elseif ($this->range) {
            $name = DateRangeType::class;
        }

        return [$name, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    protected function filter(BaseProxyQueryInterface $query, string $field, $data): void
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-mongodb-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));
        }

        //check data sanity
        if (true !== \is_array($data)) {
            return;
        }

        //default type for simple filter
        $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ? DateOperatorType::TYPE_EQUAL : (int) $data['type'];

        if (!isset($data['value'])) {
            return;
        }

        switch ($data['type']) {
            case DateOperatorType::TYPE_EQUAL:
                $this->active = true;

                $this->applyTypeIsEqual($query, $field, $data);

                return;

            case DateOperatorType::TYPE_GREATER_THAN:
                $this->active = true;

                $this->applyTypeIsGreaterThan($query, $field, $data);

                return;

            case DateOperatorType::TYPE_LESS_EQUAL:
                $this->active = true;

                $this->applyTypeIsLessEqual($query, $field, $data);

                return;

            case DateOperatorType::TYPE_GREATER_EQUAL:
            case DateOperatorType::TYPE_LESS_THAN:
                $this->active = true;

                $this->applyType($query, $this->getOperator($data['type']), $field, $data['value']);

                return;
        }
    }

    /**
     * @return void
     */
    abstract protected function applyTypeIsLessEqual(BaseProxyQueryInterface $query, string $field, array $data);

    /**
     * @return void
     */
    abstract protected function applyTypeIsGreaterThan(BaseProxyQueryInterface $query, string $field, array $data);

    /**
     * @return void
     */
    abstract protected function applyTypeIsEqual(BaseProxyQueryInterface $query, string $field, array $data);

    /**
     * @param string    $operation
     * @param string    $field
     * @param \DateTime $datetime
     */
    protected function applyType(BaseProxyQueryInterface $query, $operation, $field, ?\DateTime $datetime = null): void
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-mongodb-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));
        }

        $query->getQueryBuilder()->field($field)->$operation($datetime);
        $this->active = true;
    }

    /**
     * Resolves DataType:: constants to MongoDb operators.
     *
     * @param int $type
     *
     * @return string
     */
    protected function getOperator($type)
    {
        $choices = [
            DateOperatorType::TYPE_EQUAL => 'equals',
            DateOperatorType::TYPE_GREATER_EQUAL => 'gte',
            DateOperatorType::TYPE_GREATER_THAN => 'gt',
            DateOperatorType::TYPE_LESS_EQUAL => 'lte',
            DateOperatorType::TYPE_LESS_THAN => 'lt',
        ];

        return $choices[(int) $type];
    }
}
