<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\Extension\DiscussionTools\ThreadItem\CommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\HeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ThreadItem;

/**
 * Groups thread items (headings and comments) generated by parsing a discussion page.
 */
interface ThreadItemSet {
	/**
	 * @param ThreadItem $item
	 * @internal Only used by CommentParser
	 */
	public function addThreadItem( ThreadItem $item );

	/**
	 * @return bool
	 * @internal Only used by CommentParser
	 */
	public function isEmpty(): bool;

	/**
	 * @param ThreadItem $item
	 * @internal Only used by CommentParser
	 */
	public function updateIdAndNameMaps( ThreadItem $item );

	/**
	 * Get all discussion comments (and headings) within a DOM subtree.
	 *
	 * This returns a flat list, use getThreads() to get a tree structure starting at section headings.
	 *
	 * For example, for a MediaWiki discussion like this (we're dealing with HTML DOM here,
	 * the wikitext syntax is just for illustration):
	 *
	 *     == A ==
	 *     B. ~~~~
	 *     : C.
	 *     : C. ~~~~
	 *     :: D. ~~~~
	 *     ::: E. ~~~~
	 *     ::: F. ~~~~
	 *     : G. ~~~~
	 *     H. ~~~~
	 *     : I. ~~~~
	 *
	 * This function would return a structure like:
	 *
	 *     [
	 *       HeadingItem( { level: 0, range: (h2: A)        } ),
	 *       CommentItem( { level: 1, range: (p: B)         } ),
	 *       CommentItem( { level: 2, range: (li: C, li: C) } ),
	 *       CommentItem( { level: 3, range: (li: D)        } ),
	 *       CommentItem( { level: 4, range: (li: E)        } ),
	 *       CommentItem( { level: 4, range: (li: F)        } ),
	 *       CommentItem( { level: 2, range: (li: G)        } ),
	 *       CommentItem( { level: 1, range: (p: H)         } ),
	 *       CommentItem( { level: 2, range: (li: I)        } )
	 *     ]
	 *
	 * @return ThreadItem[] Thread items
	 */
	public function getThreadItems(): array;

	/**
	 * Same as getFlatThreadItems, but only returns the CommentItems
	 *
	 * @return CommentItem[] Comment items
	 */
	public function getCommentItems(): array;

	/**
	 * Find ThreadItems by their name
	 *
	 * This will usually return a single-element array, but it may return multiple comments if they're
	 * indistinguishable by name. In that case, use their IDs to disambiguate.
	 *
	 * @param string $name Name
	 * @return ThreadItem[] Thread items, empty array if not found
	 */
	public function findCommentsByName( string $name ): array;

	/**
	 * Find a ThreadItem by its ID
	 *
	 * @param string $id ID
	 * @return ThreadItem|null Thread item, null if not found
	 */
	public function findCommentById( string $id ): ?ThreadItem;

	/**
	 * Group discussion comments into threads and associate replies to original messages.
	 *
	 * Each thread must begin with a heading. Original messages in the thread are treated as replies to
	 * its heading. Other replies are associated based on the order and indentation level.
	 *
	 * Note that the objects in `comments` are extended in-place with the additional data.
	 *
	 * For example, for a MediaWiki discussion like this (we're dealing with HTML DOM here,
	 * the wikitext syntax is just for illustration):
	 *
	 *     == A ==
	 *     B. ~~~~
	 *     : C.
	 *     : C. ~~~~
	 *     :: D. ~~~~
	 *     ::: E. ~~~~
	 *     ::: F. ~~~~
	 *     : G. ~~~~
	 *     H. ~~~~
	 *     : I. ~~~~
	 *
	 * This function would return a structure like:
	 *
	 *     [
	 *       HeadingItem( { level: 0, range: (h2: A), replies: [
	 *         CommentItem( { level: 1, range: (p: B), replies: [
	 *           CommentItem( { level: 2, range: (li: C, li: C), replies: [
	 *             CommentItem( { level: 3, range: (li: D), replies: [
	 *               CommentItem( { level: 4, range: (li: E), replies: [] } ),
	 *               CommentItem( { level: 4, range: (li: F), replies: [] } ),
	 *             ] } ),
	 *           ] } ),
	 *           CommentItem( { level: 2, range: (li: G), replies: [] } ),
	 *         ] } ),
	 *         CommentItem( { level: 1, range: (p: H), replies: [
	 *           CommentItem( { level: 2, range: (li: I), replies: [] } ),
	 *         ] } ),
	 *       ] } )
	 *     ]
	 *
	 * @return HeadingItem[] Tree structure of comments, top-level items are the headings.
	 */
	public function getThreads(): array;
}
