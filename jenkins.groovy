def final REPO_NAME = 'cakephp/cakephp-api-docs'
def final CAKE_REPO_NAME = 'cakephp/cakephp'
def final CHRONOS_REPO_NAME = 'cakephp/chronos'

job('API - Rebuild All API docs') {
  description('''\
  Will delete all API doc websites and rebuild them. Useful for fixing templates.
  ''')
  multiscm {
    git {
      remote {
        github(REPO_NAME)
      }
      branch('master')
    }
    git {
      remote {
        github(CAKE_REPO_NAME)
      }
      branches('master', '3.next', '2.x', '2.next')
    }
    git {
      remote {
        github(CHRONOS_REPO_NAME)
      }
      branch('master')
    }
  }
  triggers {
    githubPush()
  }
  logRotator {
    daysToKeep(30)
  }
  steps {
    shell('''
rm -rf /tmp/apidocs-$GIT_COMMIT
git clone https://github.com/cakephp/cakephp-api-docs.git /tmp/apidocs-$GIT_COMMIT
cd /tmp/apidocs-$GIT_COMMIT
touch "$GIT_COMMIT"
git add "$GIT_COMMIT"
git commit --author "Jenkins <ci@cakephp.org>" -m "Regenerate for commit $GIT_COMMIT"

git remote rm origin
git remote | grep dokku || git remote add dokku dokku@new.cakephp.org:api
git push -fv dokku master
rm -rf /tmp/apidocs-$GIT_COMMIT
    ''')
  }
}
