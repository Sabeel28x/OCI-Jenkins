pipeline {
    agent any
    stages {
        stage('Debug Environment') {
            steps {
                script {
                    sh 'echo $PATH'
                    sh 'which oci || echo "OCI CLI not found!"'
                    sh 'oci --version || echo "OCI CLI not working!"'
                }
            }
        }
    }
}
