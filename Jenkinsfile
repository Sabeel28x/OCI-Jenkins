pipeline {
    agent any
    triggers {
        githubPush()
    }
    environment {
        OCI_POOL_ID = 'ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaazi3z5myovpdtc45qensdkwuqgbyq4lkgeqkz5ish2ij7tfezg6pq'
        OCI_COMPARTMENT_ID = 'ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q'
        REPO_URL = 'https://github.com/Sabeel28x/OCI-Jenkins.git'
        BRANCH = 'main'
        APACHE_DOC_ROOT = '/var/www/html'
        TEAMS_WEBHOOK_URL = 'https://prod-24.centralindia.logic.azure.com:443/workflows/c01ef101a97a49aaaa3df9d6446738b9/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=u-ZjwsTHFM5tP0U7I6SHvKpWP6YLhIvyjKlDMf2EUck'
    }
    stages {
        stage('Fetch Instance IPs') {
            steps {
                script {
                    echo "Fetching instance list from OCI Instance Pool..."
                    
                    // Fetch instance IDs from the instance pool
                    def instanceList = sh(script: """
                        oci compute-management instance-pool list-instances \
                            --instance-pool-id ${OCI_POOL_ID} \
                            --compartment-id ${OCI_COMPARTMENT_ID} \
                            --output json
                    """, returnStdout: true).trim()
                    
                    def instances = readJSON text: instanceList
                    
                    // Extract instance IDs
                    def instanceIds = instances.data.collect { it.id }
                    
                    // Fetch private IPs for each instance
                    def instanceIps = []
                    instanceIds.each { instanceId ->
                        def vnicInfo = sh(script: """
                            oci compute instance list-vnics \
                                --instance-id ${instanceId} \
                                --output json
                        """, returnStdout: true).trim()
                        
                        def vnics = readJSON text: vnicInfo
                        def privateIp = vnics.data[0]['private-ip']
                        instanceIps.add(privateIp)
                    }
                    
                    // Ensure IPs were collected
                    if (instanceIps.isEmpty()) {
                        error("No private IPs found. Cannot proceed with deployment.")
                    }
                    
                    echo "Private IPs: ${instanceIps}"
                    env.INSTANCE_IPS = instanceIps.join(',')
                }
            }
        }

        stage('Deploy Code to Instances') {
            steps {
                script {
                    // Split the IPs into a list
                    def instanceIps = env.INSTANCE_IPS.split(',')

                    // Check if the IPs list is empty or null
                    if (instanceIps.length == 0 || instanceIps[0] == '') {
                        error("No valid IPs found. Cannot proceed with deployment.")
                    }

                    // Inject SSH credentials and deploy the code
                    sshagent(['oci']) {
                        instanceIps.each { ip ->
                            echo "Deploying code to instance with IP: ${ip}"

                            // Use sudo -i to execute commands as root in an interactive shell
                            sh """
                                ssh -o StrictHostKeyChecking=no opc@${ip} '
                                    cd ${APACHE_DOC_ROOT} && \
                                    sudo git clone ${REPO_URL} && \
                                    sudo mv OCI-Jenkins/index.php /var/www/html && \
                                    sudo rm -rf OCI-Jenkins && \
                                    sudo systemctl restart httpd
                                '
                            """
                        }
                    }
                }
            }
        }
    }
    post {
    success {
        script {
            // Notify via Microsoft Teams on success
            withCredentials([string(credentialsId: 'teams-webhook-url', variable: 'TEAMS_WEBHOOK_URL')]) {
                def successMessage = """
                    {
                        "text": "✅ *Code Deployment Success*\n
                        *Job Name:* ${env.JOB_NAME}\n
                        *Build Number:* ${env.BUILD_NUMBER}\n
                        *Instances Deployed:*\n${env.INSTANCE_IPS.replaceAll(',', '\\n')}\n
                        *Repository:* ${REPO_URL}\n
                        *Branch:* ${BRANCH}"
                    }
                """
                sh """
                    curl -H 'Content-Type: application/json' -d '${successMessage}' ${TEAMS_WEBHOOK_URL}
                """
            }
        }
    }
    failure {
        script {
            // Notify via Microsoft Teams on failure
            withCredentials([string(credentialsId: 'teams-webhook-url', variable: 'TEAMS_WEBHOOK_URL')]) {
                def failureMessage = """
                    {
                        "text": "❌ *Code Deployment Failure*\n
                        *Job Name:* ${env.JOB_NAME}\n
                        *Build Number:* ${env.BUILD_NUMBER}\n
                        *Repository:* ${REPO_URL}\n
                        *Branch:* ${BRANCH}"
                    }
                """
                sh """
                    curl -H 'Content-Type: application/json' -d '${failureMessage}' ${TEAMS_WEBHOOK_URL}
                """
            }
        }
    }
  }
}
