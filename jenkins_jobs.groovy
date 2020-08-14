def final REPO_NAME = 'cakephp/cakephp-api-docs'
def final CAKE_REPO_NAME = 'cakephp/cakephp'
def final CHRONOS_REPO_NAME = 'cakephp/chronos'
def final ELASTICSEARCH_REPO_NAME = 'cakephp/elastic-search'

job('API - Rebuild API docs 4.x') {
  description('''\
  Will delete the 4.x API doc websites and rebuild them.
  ''')
  multiscm {
    git {
      remote {
        github(REPO_NAME)
      }
      branch('2.x')
    }
    git {
      remote {
        github(CAKE_REPO_NAME)
      }
      branches('master', '3.x')
    }
    git {
      remote {
        github(CHRONOS_REPO_NAME)
      }
      branch('master')
    }
    git {
      remote {
        github(ELASTICSEARCH_REPO_NAME)
      }
      branch('master')
    }
  }
  triggers {
    scm('H/5 * * * *')
  }
  logRotator {
    daysToKeep(30)
  }
  steps {
    shell('''
rm -rf /tmp/apidocs-$GIT_COMMIT
git clone https://github.com/cakephp/cakephp-api-docs.git /tmp/apidocs-$GIT_COMMIT
cd /tmp/apidocs-$GIT_COMMIT
git checkout 2.x
touch "$GIT_COMMIT"
git add "$GIT_COMMIT"
git commit --author "Jenkins <ci@cakephp.org>" -m "Regenerate for commit $GIT_COMMIT"

git remote rm origin
git remote rm dokku || true
git remote | grep dokku || git remote add dokku dokku@apps.cakephp.org:api-4
git push -fv dokku 2.x:master
rm -rf /tmp/apidocs-$GIT_COMMIT
    ''')
  }

  publishers {
    slackNotifier {
      room('#dev')
      notifyFailure(true)
      notifyRepeatedFailure(true)
    }
  }

}
