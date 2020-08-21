<?php
use MediaWiki\MediaWikiServices;

class SpecialBlogPostIndex extends IncludableSpecialPage {

    private $limit = null;
    private $namespace = null;
    private $redirects = null;

    public function __construct() {
        parent::__construct( 'BlogPostIndex', '', true, false, 'default', true );
    }

    /**
     * @inheritDoc
     */
    public function execute( $par ) {
        $language = MediaWikiServices::getInstance()->getContentLanguage();

        $out = $this->getOutput();
        $request = $this->getRequest();

        # Decipher input passed to the page
        $this->decipherParams( $par );
        $this->setOptions( $request );

        // Load style for class="allpagesredirect"
        $out->addModuleStyles( 'mediawiki.special' );

        $dbr = wfGetDB( DB_REPLICA );

        $conds = [];
        #$conds[] = $this->getNsFragment();
        $conds['page_namespace'] = NS_POST;
        if ( !$this->redirects ) {
            $conds['page_is_redirect'] = 0;
        }

        $res = $dbr->select(
            [ 'page' ],
            [
                'page_namespace', 'page_title', 'page_is_redirect',
                'page_id',
            ],
            $conds,
            __METHOD__,
            [
                'ORDER BY' => 'page_id DESC',
                'LIMIT' => "{$this->limit}"
            ]
        );

        $newestBlogPosts = [];
        foreach ( $res as $row ) {
            $titleObj = Title::makeTitle( NS_POST, $row->page_title );
            $created = self::getCreateDate($row->page_id);
            $newestBlogPosts[substr($created, 0, 6)][] = [
                'title' => $titleObj,
                //'ns' => $row->page_namespace,
                //'id' => $row->page_id,
                'created' => $created
            ];
        }


        $count = $dbr->numRows( $res );

        # Don't show the navigation if we're including the page
        if ( !$this->including() ) {
            $this->setHeaders();
            $limit = $this->getLanguage()->formatNum( $this->limit );
            if ( $this->namespace > 0 ) {
                $out->addWikiMsg( 'blogpostindex-ns-header', $limit,
                    $language->getFormattedNsText( $this->namespace ) );
            } else {
                $out->addWikiMsg( 'blogpostindex-header', $limit );
            }
            $out->addHTML( '<p>' . $this->makeLimitLinks() );
            $out->addHTML( '<br />' . $this->makeRedirectToggle() . '</p>' );
        }

        if ( $count > 0 ) {
            # Make list
            if ( !$this->including() ) {
                $out->addWikiMsg( 'blogpostindex-showing', $this->getLanguage()->formatNum( $count ) );
            }

            $output = [];
            $linkRenderer = $this->getLinkRenderer();
            foreach ( array_keys($newestBlogPosts) as $date) {
                $output[] = "<h5>" . DateTime::createFromFormat('Ym', $date)->format('F Y') . "</h5>";
                $output[] = "<ul>";
                foreach ( $newestBlogPosts[$date] as $newestBlogPost ) {
                    $output[] = "<li>" . $linkRenderer->makeKnownLink($newestBlogPost['title'], $newestBlogPost['title']->getSubpageText()) . "</li>";
                }
                $output[] = "</ul>";
            };
            $out->addHTML(implode("\n", $output));
        } else {
            $out->addWikiMsg( 'blogpostindex-none' );
        }
    }

    private static function getCreateDate( $pageId ) {
        wfDebugLog( 'SimpleBlogPage', "Loading create_date for page {$pageId} from database" );
        $dbr = wfGetDB( DB_REPLICA );
        $createDate = $dbr->selectField(
            'revision',
            'rev_timestamp', // 'UNIX_TIMESTAMP(rev_timestamp) AS create_date',
            [ 'rev_page' => $pageId ],
            __METHOD__,
            [ 'ORDER BY' => 'rev_timestamp ASC' ]
        );

        return $createDate;
    }

    /**
     * @param WebRequest $req
     */
    private function setOptions( WebRequest $req ) {
        $newestPagesLimit = $this->getConfig()->get( 'BlogPostIndexLimit' );
        if ( !isset( $this->limit ) ) {
            $this->limit = $this->sanitiseLimit( $req->getInt( 'limit', $newestPagesLimit ) );
        }
        if ( !isset( $this->namespace ) ) {
            $this->namespace = $this->extractNamespace( $req->getVal( 'namespace', -1 ) );
        }
        if ( !isset( $this->redirects ) ) {
            $this->redirects = (bool)$req->getInt( 'redirects', 1 );
        }
    }

    private function sanitiseLimit( $limit ) {
        return min( (int)$limit, 5000 );
    }

    private function decipherParams( $par ) {
        if ( $par ) {
            $bits = explode( '/', $par );
            foreach ( $bits as $bit ) {
                if ( is_numeric( $bit ) ) {
                    $this->limit = $this->sanitiseLimit( $bit );
                } else {
                    $this->namespace = $this->extractNamespace( $bit );
                }
            }
        }
    }

    private function extractNamespace( $namespace ) {
        $language = MediaWikiServices::getInstance()->getContentLanguage();
        if ( is_numeric( $namespace ) ) {
            return $namespace;
        } elseif ( $language->getNsIndex( $namespace ) !== false ) {
            return $language->getNsIndex( $namespace );
        } elseif ( $namespace == '-' ) {
            return NS_MAIN;
        } else {
            return -1;
        }
    }

    private function getNsFragment() {
        $this->namespace = (int)$this->namespace;
        return $this->namespace > -1 ? "page_namespace = {$this->namespace}" : 'page_namespace != 8';
    }

    private function makeListItem( $row ) {
        $title = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
        $linkRenderer = $this->getLinkRenderer();
        if ( $title !== null ) {
            $link = $row->page_is_redirect
                ? '<span class="allpagesredirect">' . $linkRenderer->makeKnownLink( $title ) . '</span>'
                : $linkRenderer->makeKnownLink( $title );
            return "<li>{$link}</li>\n";
        } else {
            return "<!-- Invalid title " . htmlspecialchars( $row->page_title ) .
                " in namespace " . htmlspecialchars( $row->page_namespace ) . " -->\n";
        }
    }

    private function makeLimitLinks() {
        $lang = $this->getLanguage();
        $limits = [ 10, 20, 30, 50, 100, 150 ];
        $links = [];
        foreach ( $limits as $limit ) {
            if ( $limit != $this->limit ) {
                $links[] = $this->makeSelfLink( $lang->formatNum( $limit ), 'limit', $limit );
            } else {
                $links[] = (string)$limit;
            }
        }
        return $this->msg( 'blogpostindex-limitlinks' )->rawParams(
            $lang->pipeList( $links ) )->escaped();
    }

    private function makeRedirectToggle() {
        $label = $this->msg(
            $this->redirects ? 'blogpostindex-hideredir' : 'blogpostindex-showredir' )->text();
        return $this->makeSelfLink( $label, 'redirects', (int)!$this->redirects );
    }

    private function makeSelfLink( $label, $oname = false, $oval = false ) {
        $linkRenderer = $this->getLinkRenderer();
        $self = $this->getPageTitle();
        $attr = [];
        $attr['limit'] = $this->limit;
        $attr['namespace'] = $this->namespace;

        if ( !$this->redirects ) {
            $attr['redirects'] = 0;
        }

        if ( $oname ) {
            $attr[$oname] = $oval;
        }

        return $linkRenderer->makeKnownLink( $self, $label, [], $attr );
    }

    /**
     * @inheritDoc
     */
    protected function getGroupName() {
        return 'changes';
    }
}
