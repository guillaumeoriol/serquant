<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Service;

/**
 * Promises that a service layer should fulfill to manage persistent entities.
 *
 * Basic CRUD operations ({@link create()}, {@link retrieve()}, {@link update()}
 * and {@link delete()}), plus several flavors of the fetch method and
 * a not-usual {@link getDefault()} (for getting the entity default state)
 * make up the promises of this service layer.
 *
 * <i>Note: as we are in a service context and not in a REST context,
 * we speak about entities and not resources.</i>
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface Persistable
{
    /**
     * Retrieve a collection of entities matching the specified query
     * expressions.
     *
     * Each query expression of the <var>expressions</var> parameter represents
     * an operator of the Resource Query Language as defined in the
     * {@link https://github.com/kriszyp/rql RQL specification}.
     *
     * <ul>
     * <li>Simple filters are specified with <code>field=value</code> pairs.
     *     For more complex filters, refer to the
     *     {@link https://github.com/kriszyp/rql Resource Query Language
     *     documentation}.</li>
     * <li>Range is specified by the
     *     <code>limit(&lt;start&gt;,&lt;count&gt;)</code> query parameter.
     *     As an alternative, it may also be specified by the <code>Range</code>
     *     request header (<code>Range: items=&lt;start&gt;-&lt;end&gt;</code>)
     *     that is extracted from request headers by the {@link RangeHandler}
     *     controller plugin and injected into the query (with
     *     <code>count = end - start + 1</code>).</li>
     * <li>Sort is specified by <code>sort(+field)</code> or
     *     <code>sort(-field)</code> query parameter. Multiple property sorts
     *     may be achieved with the following syntax:
     *     <code>sort(+category,-price)</code>.</li>
     * </ul>
     *
     * If there is no result, an empty collection is returned with a 0 status.
     *
     * <b>Design considerations</b>
     *
     * When fetching a collection of entities, we often don't want to retrieve
     * the complete set but only a subset of the collection. To do so, we need
     * to provide conditions that will narrow down the fetch.
     * In its very basic form, a condition may be expressed as follow:
     * "fetch items which category is 'oil'". In the URI context, it would
     * be written: "?category=oil" (query component of the URI). In the SQL
     * context, it would be written as a WHERE clause: "WHERE category = 'oil'".
     * But what syntax should we use to express more complex conditions like
     * "items which catagory is 'oil' or 'vinegar'"?
     * At the time of this writing (august, 2010), no unique standard has
     * emerged to define such a syntax, but some proposals have been made:
     * <ul>
     * <li>Major companies have published API for their public services.
     *     For example, Google uses "|" and "," between values in its gdata
     *     API to represent respectively OR and AND operators. These are not
     *     generic enough.
     *     Cf. {@link http://code.google.com/intl/fr/apis/gdata/docs/2.0/reference.html#Queries}</li>
     * <li>FIQL (pronounced "fickle") is a simple but flexible, URI-friendly
     *     syntax for expressing filters across the entries in a syndicated
     *     feed. FIQL does not specify how to represent sort and range.
     *     Cf. {@link http://tools.ietf.org/html/draft-nottingham-atompub-fiql-00}</li>
     * <li>Jaql is a query language designed for JSON. Jaql is primarily used
     *     to analyze large-scale semi-structured data. Core features include
     *     user extensibility and parallelism. In addition to modeling semi-
     *     structured data, JSON simplifies extensibility.
     *     Cf. {@link http://code.google.com/p/jaql/}</li>
     * <li>JSONQuery (first developped for Dojo and Persevere) is based on
     *     the specification for JSONPath and has been greatly expanded with
     *     numerous additional features for more powerful querying capabilities.
     *     This expanded set of functionality is called JSONQuery. JSONQuery is
     *     not really URI friendly.
     *     Cf. {@link http://docs.persvr.org/documentation/jsonquery}</li>
     * <li>Resource Query Language by Kris Zyp (from SitePen) defines a
     *     syntactically simple query language for querying resources.
     *     Cf. {@link http://www.persvr.org/draft-zyp-rql-00.html}</li>
     * <li>YQL (Yahoo! Query Language) uses a SQL-like language to query,
     *     filter, and join data across Web services. Sample syntax:
     *     https://query.yahooapis.com/v1/public/yql?
     *     q={uri-encoded-query}&
     *     format={xml|json}&
     *     diagnostics={true|false}&
     *     callback={function}&
     *     env=http://datatables.org/alltables.env
     *     Cf. {@link http://developer.yahoo.com/yql/}</li>
     * </ul>
     *
     * I decided to adopt RQL for being easy, URI-friendly, written by a Dojo
     * committer and implementing a "sort" syntax already in use in the
     * dojox.grid.DataGrid implementation.
     *
     * @param array $expressions Array of query expressions
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * the fetched collection of entities.
     * @throws Exception on failure.
     */
    public function fetchAll(array $expressions);

    /**
     * Retrieve a single entity matching the specified query.
     *
     * Each query expression of the <var>expressions</var> parameter represents
     * an operator of the Resource Query Language as defined in the
     * {@link https://github.com/kriszyp/rql RQL specification}.
     *
     * @param array $expressions Array of query expressions.
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * the fetched entity.
     * @throws Exception on failure.
     */
    public function fetchOne(array $expressions);

    /**
     * Retrieve a paginated collection of entities matching the specified query
     * expressions.
     *
     * Each query expression of the <var>expressions</var> parameter represents
     * an operator of the Resource Query Language as defined in the
     * {@link https://github.com/kriszyp/rql RQL specification}.
     *
     * @param array $expressions Array of query expressions.
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * a \Zend_Paginator instance.
     * @throws Exception on failure.
     */
    public function fetchPage(array $expressions);

    /**
     * Get a new entity in its initial state.
     *
     * All properties of the entity must be present, even if their values are
     * null.
     *
     * @return Result
     * Result#getStatus() always returns 0 and Result#getData() returns the new
     * entity.
     * @throws Exception on failure.
     */
    public function getDefault();

    /**
     * Create an entity from the given data.
     *
     * @param array $data Input data, in the form of name/value pairs.
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the created entity.<br> When validation fails,
     * Result#getStatus() returns 1 (validation error) and Result#getErrors()
     * returns a collection of constraint violations.
     * @throws Exception on failure (other than validation failure).
     */
    public function create(array $data);

    /**
     * Retrieve an entity matching the specified identifier.
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the retrieved entity.
     * @throws Exception When an error occurred or when the identifier is
     * missing.
     */
    public function retrieve($id = null);

    /**
     * Update the given entity.
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @param array $data Input data, in the form of name/value pairs.
     * Names shall match entity field names.
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the updated entity.<br> When validation fails,
     * Result#getStatus() returns 1 (validation error) and Result#getErrors()
     * returns a collection of constraint violations.
     * @throws Exception on failure (other than validation failure) or when the
     * identifier is missing.
     */
    public function update($id, array $data);

    /**
     * Delete the entity matching the specified identifier.
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the deleted entity.
     * @throws Exception When an error occurred or when the identifier is
     * missing.
     */
    public function delete($id = null);
}