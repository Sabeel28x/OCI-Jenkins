pipeline {
    agent any
    triggers {
        githubPush()
    }
    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', credentialsId: 'oci-github', url: 'https://github.com/Sabeel28x/OCI-Jenkins'
            }
        }
        stage('Build') {
            steps {
                sh 'echo "Building..."'
            }
        }
    }
}
