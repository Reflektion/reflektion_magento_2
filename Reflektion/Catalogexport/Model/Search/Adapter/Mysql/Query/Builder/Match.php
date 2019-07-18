<?php
namespace Reflektion\Catalogexport\Model\Search\Adapter\Mysql\Query\Builder;

use Magento\Framework\DB\Helper\Mysql\Fulltext;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\Field\FieldInterface;
use Magento\Framework\Search\Adapter\Mysql\Field\ResolverInterface;
use Magento\Framework\Search\Adapter\Mysql\ScoreBuilder;
use Magento\Framework\Search\Request\Query\BoolExpression;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\Search\Adapter\Preprocessor\PreprocessorInterface;

class Match extends \Magento\Framework\Search\Adapter\Mysql\Query\Builder\Match
{
    /**
     * @param ResolverInterface $resolver
     * @param Fulltext $fulltextHelper
     * @param string $fulltextSearchMode
     * @param PreprocessorInterface[] $preprocessors
     */
    public function __construct(
        ResolverInterface $resolver,
        Fulltext $fulltextHelper,
        $fulltextSearchMode = Fulltext::FULLTEXT_MODE_BOOLEAN,
        array $preprocessors = []
    ) {
        parent::__construct(
            $resolver,
            $fulltextHelper,
            $fulltextSearchMode,
            $preprocessors
        );
    }

    public function build(
        ScoreBuilder $scoreBuilder,
        Select $select,
        RequestQueryInterface $query,
        $conditionType
    )
    {
        return $query;
    }
}