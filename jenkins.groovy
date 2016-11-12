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
        github(REPO_NAME, 'master')
      }
    }
    git {
      remote {
        github(CAKE_REPO_NAME, 'master')
      }
    }
    // github(CAKE_REPO_NAME, '2.x')
    // github(CAKE_REPO_NAME, '2.next')
    // github(CAKE_REPO_NAME, '3.next')
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
touch "$GIT_COMMIT" && git add "$GIT_COMMIT" && git commit -m "Regenerate for commit $GIT_COMMIT"

git remote rm origin
ssh-keyscan -t rsa 104.239.163.8 >> ~/.ssh/known_hosts
git remote | grep dokku || git remote add dokku dokku@104.239.163.8:api
git push -fv dokku master
rm -rf /tmp/apidocs-$GIT_COMMIT
    ''')
  }
}
