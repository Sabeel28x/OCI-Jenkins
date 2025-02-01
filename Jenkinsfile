pipeline {
    agent any
    triggers {
        githubPush()
    }
    environment {
        OCI_POOL_ID = 'ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaa4ndkxh7cjzfxssujxpylukjlglgtl2gp7bgw6bcpglbo62gsw5zq'
        OCI_COMPARTMENT_ID = 'ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q'
        REPO_URL = 'https://github.com/Sabeel28x/OCI-Jenkins.git'
        BRANCH = 'main'
        APACHE_DOC_ROOT = '/var/www/html'
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
                    Q
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
            echo "Code deployment to all instances completed successfully!"
        }
        failure {
            echo "Code deployment failed."
        }
    }
}
