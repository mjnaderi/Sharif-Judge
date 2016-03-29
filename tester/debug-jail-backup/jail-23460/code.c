#define main themainmainfunction

#include <iostream>
#include <cstring>

using namespace std;

int eval_solution(int *job, int *solution, int n, int m){
    int *total_time = new int [m];
    memset(total_time, 0, m*sizeof(m));

    int t_max = job[0];
    int m_max = solution[0];

    for(int i = 0; i < n; i++){
        int a = total_time[solution[i]] += job[i];
        if (a > t_max){
            t_max = a;
            m_max = i;
        }
    }

//    for(int i = 0; i < m; i++){
//        cout << "machine " << i << " took " << total_time[i] << endl;
//    }

    return t_max;
}

int main()
{
    int n, m;
    cin >> n ;

    int *job = new int [n];
    for(int i = 0; i < n; i++){
        cin >> job[i];
    }

    //Selection sort.
    for(int i = 0; i < n; i++){
        int min_idx = -1;
        int min_val = -1;
        for(int j = 0; j < n; j++){
            if ( job[j] != -1
                && (min_val == -1 || job[j] < min_val)
            )
            {
                min_val = job[j];
                min_idx = j;

            }
        }
        cout << min_idx << " ";
        job[min_idx] = -1;

    }
    cout << endl;
    return 0;
}
